<?php

namespace App\Services;

use App\Models\ApplicationStatus;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerProfessionalDetail;
use App\Models\Lead;
use App\Models\LeadAssignment;
use App\Models\LeadStatus;
use App\Models\LoanApplication;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * All lead lifecycle logic lives here so controllers (and any future API
 * controllers) stay thin and the workflow rules exist in exactly one place.
 */
class LeadService
{
    public function __construct(
        protected CodeGeneratorService $codes,
        protected NotificationService $notifications,
    ) {
    }

    /** Create a lead together with its customer (or attach an existing one). */
    public function createLead(array $data, User $actor): Lead
    {
        return DB::transaction(function () use ($data, $actor) {
            $customer = $this->resolveCustomer($data, $actor);

            $lead = new Lead();
            $lead->fill([
                'lead_code' => $this->codes->lead(),
                'customer_id' => $customer->id,
                'lead_source_id' => $data['lead_source_id'] ?? null,
                'lead_type' => $data['lead_type'] ?? 'fresh',
                'customer_type' => $data['customer_type'] ?? 'new',
                'preferred_contact_method' => $data['preferred_contact_method'] ?? 'call',
                'preferred_contact_time' => $data['preferred_contact_time'] ?? null,
                'assigned_to' => $data['assigned_to'] ?? $actor->id,
                'created_by' => $actor->id,
                'current_step' => 1,
                'is_draft' => true,
                'status' => 'draft',
                'last_activity_at' => now(),
            ]);
            $lead->save();

            $lead->recordLeadStatus(
                LeadStatus::query()->where('slug', 'draft')->first()
                    ?? LeadStatus::query()->orderBy('stage_order')->firstOrFail(),
                'Lead created'
            );

            $this->assign($lead, (int) ($data['assigned_to'] ?? $actor->id), $actor, 'Initial assignment');

            $this->saveCustomerDetails($customer, $data);

            $this->notifyAssignment($lead, $actor);

            return $lead->refresh();
        });
    }

    public function resolveCustomer(array $data, User $actor): Customer
    {
        if (! empty($data['customer_id'])) {
            return Customer::query()->findOrFail($data['customer_id']);
        }

        $mobile = preg_replace('/\D/', '', (string) ($data['mobile'] ?? ''));

        $existing = Customer::query()->where('mobile', $mobile)->first();

        if ($existing) {
            return $existing;
        }

        $customer = Customer::query()->create([
            'customer_code' => $this->codes->customer(),
            'name' => $data['name'] ?? 'Unnamed Customer',
            'mobile' => $mobile,
            'email' => $data['email'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'gender' => $data['gender'] ?? null,
            'marital_status' => $data['marital_status'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'pincode' => $data['pincode'] ?? null,
            'address' => $data['address'] ?? null,
            'customer_type' => $data['customer_type'] ?? 'individual',
            'status' => 'active',
            'kyc_status' => 'pending',
            'assigned_employee_id' => $data['assigned_to'] ?? $actor->id,
            'created_by' => $actor->id,
        ]);

        if (! empty($data['address'])) {
            CustomerAddress::query()->create([
                'customer_id' => $customer->id,
                'address_type' => 'residential',
                'address_line' => $data['address'],
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'pincode' => $data['pincode'] ?? null,
                'is_primary' => true,
            ]);
        }

        return $customer;
    }

    public function saveCustomerDetails(Customer $customer, array $data, bool $professional = false): void
    {
        $customer->fill(Arr::only($data, [
            'name', 'email', 'date_of_birth', 'gender', 'marital_status', 'city', 'state', 'pincode',
            'address', 'father_or_spouse_name', 'pan_number', 'aadhaar_number', 'nationality',
            'employment_type_id', 'company_name', 'designation', 'monthly_income', 'annual_income',
            'work_experience_years', 'office_address', 'occupation', 'alternate_mobile', 'customer_type',
        ]));

        if (! empty($data['mobile'])) {
            $customer->mobile = preg_replace('/\D/', '', (string) $data['mobile']);
        }

        $customer->save();

        if ($professional && (($data['company_name'] ?? null) || ($data['employment_type_id'] ?? null))) {
            CustomerProfessionalDetail::query()->updateOrCreate(
                ['customer_id' => $customer->id, 'is_current' => true],
                Arr::only($data, [
                    'employment_type_id', 'company_name', 'designation', 'monthly_income', 'annual_income',
                    'work_experience_years', 'office_address', 'gst_number', 'industry', 'company_type',
                    'business_vintage_years',
                ])
            );
        }
    }

    public function updateStep(Lead $lead, int $step, array $data): Lead
    {
        return DB::transaction(function () use ($lead, $step, $data) {
            $lead->fill(Arr::only($data, [
                'product_id', 'product_category_id', 'product_subcategory_id', 'employment_type_id',
                'loan_amount', 'tenure_months', 'preferred_bank', 'monthly_income', 'existing_emi',
                'credit_score', 'notes', 'lead_source_id', 'lead_type', 'customer_type',
                'preferred_contact_method', 'preferred_contact_time', 'priority', 'assigned_to',
                'expected_commission',
            ]));

            $lead->current_step = max($lead->current_step, $step);
            $lead->last_activity_at = now();
            $lead->save();

            if ($lead->customer) {
                $this->saveCustomerDetails($lead->customer, $data, $professional = $step >= 5);
            }

            return $lead->refresh();
        });
    }

    public function assign(Lead $lead, int $userId, User $actor, ?string $note = null): void
    {
        $lead->assignments()->update(['is_current' => false]);

        LeadAssignment::query()->create([
            'lead_id' => $lead->id,
            'assigned_to' => $userId,
            'assigned_by' => $actor->id,
            'assigned_at' => now(),
            'is_current' => true,
            'note' => $note,
        ]);

        $lead->forceFill(['assigned_to' => $userId])->save();

        if ($userId !== $actor->id) {
            $this->notifyAssignment($lead, $actor);
        }
    }

    public function changeStatus(Lead $lead, string $statusSlug, ?string $note, User $actor): Lead
    {
        $status = LeadStatus::query()->where('slug', $statusSlug)->firstOrFail();

        $lead->recordLeadStatus($status, $note);

        $this->notifications->send(
            [$lead->assignee, $lead->creator],
            'lead-updated',
            'Lead status updated',
            sprintf('%s moved to %s', $lead->lead_code, $status->name),
            ['url' => route('leads.show', $lead), 'actor_id' => $actor->id, 'data' => ['lead_id' => $lead->id]]
        );

        return $lead->refresh();
    }

    /** Convert a lead into a loan application once lenders are selected. */
    public function convertToApplication(Lead $lead, User $actor, bool $force = false): LoanApplication
    {
        if ($lead->status === 'draft' && ! $force) {
            throw new \RuntimeException('Complete the lead wizard before creating an application.');
        }

        if ($existing = $lead->applications()->latest()->first()) {
            return $existing;
        }

        return DB::transaction(function () use ($lead, $actor) {
            $primary = $lead->lenders()->where('is_primary', true)->first()
                ?? $lead->lenders()->orderBy('roi')->first();

            $application = LoanApplication::query()->create([
                'application_code' => $this->codes->application(),
                'lead_id' => $lead->id,
                'customer_id' => $lead->customer_id,
                'product_id' => $lead->product_id ?? Product::query()->where('slug', Product::LOANS)->value('id'),
                'product_category_id' => $lead->product_category_id,
                'product_subcategory_id' => $lead->product_subcategory_id,
                'status' => 'application-created',
                'loan_amount' => $lead->loan_amount ?? 0,
                'tenure_months' => $lead->tenure_months,
                'roi' => $primary?->roi,
                'apr' => $primary?->apr,
                'emi' => $primary?->emi,
                'processing_fee' => $primary?->processing_fee,
                'primary_lender_id' => $primary?->lender_id,
                'preferred_bank' => $lead->preferred_bank,
                'employment_type' => $lead->employmentType?->name,
                'monthly_income' => $lead->monthly_income,
                'existing_emi' => $lead->existing_emi,
                'credit_score' => $lead->credit_score,
                'assigned_to' => $lead->assigned_to,
                'created_by' => $actor->id,
                'submitted_at' => now(),
                'notes' => $lead->notes,
            ]);

            if ($status = ApplicationStatus::query()->where('slug', 'application-created')->first()) {
                $application->recordApplicationStatus($status, 'Application created from lead '.$lead->lead_code);
            }

            $lead->lenders()->update(['loan_application_id' => $application->id]);

            $lead->forceFill([
                'is_draft' => false,
                'is_converted' => true,
                'converted_at' => now(),
            ])->save();

            if ($status = LeadStatus::query()->where('slug', 'application-created')->first()) {
                $lead->recordLeadStatus($status, 'Application '.$application->application_code.' created');
            }

            $this->notifications->send(
                [$lead->assignee, $lead->creator],
                'application-created',
                'Application created',
                sprintf('%s created for %s', $application->application_code, $lead->customer?->name),
                ['url' => route('applications.show', $application), 'actor_id' => $actor->id]
            );

            return $application;
        });
    }

    protected function notifyAssignment(Lead $lead, User $actor): void
    {
        if (! $lead->assignee) {
            return;
        }

        $this->notifications->send(
            [$lead->assignee],
            'lead-assigned',
            'New lead assigned',
            sprintf('%s has been assigned to you', $lead->lead_code),
            ['url' => route('leads.show', $lead), 'actor_id' => $actor->id, 'data' => ['lead_id' => $lead->id]]
        );
    }
}
