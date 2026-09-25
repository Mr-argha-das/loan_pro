<?php

namespace Database\Seeders;

use App\Models\ApplicationStatus;
use App\Models\Attendance;
use App\Models\Customer;
use App\Models\Disbursement;
use App\Models\Document;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\Leave;
use App\Models\Lender;
use App\Models\LenderProduct;
use App\Models\LoanApplication;
use App\Models\LoanLender;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSubcategory;
use App\Models\Remark;
use App\Models\User;
use App\Services\CodeGeneratorService;
use App\Services\NotificationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Realistic operating data so every dashboard, report and listing page has
 * something meaningful to show straight after installation.
 */
class DemoDataSeeder extends Seeder
{
    protected CodeGeneratorService $codes;

    protected NotificationService $notifications;

    protected array $employees = [];

    protected array $sources = [];

    protected array $customerPool = [];

    public function run(): void
    {
        if (Customer::query()->exists()) {
            $this->command?->warn('Demo data already present - skipping.');

            return;
        }

        $this->codes = app(CodeGeneratorService::class);
        $this->notifications = app(NotificationService::class);
        $this->employees = User::query()->whereHas('role', fn ($q) => $q->where('slug', 'employee'))->get()->all();
        $this->sources = LeadSource::query()->pluck('id', 'code')->all();

        $this->seedCustomers();
        $this->seedLeadsAndApplications();
        $this->seedInvoicesAndPayments();
        $this->seedAttendance();
    }

    protected function seedCustomers(): void
    {
        $people = [
            ['Rohit Sharma', '9876543210', 'rohit.sharma@gmail.com', 'Mumbai', 'Maharashtra', '400058', 'male', 'married', 'Salaried', 'Infosys Ltd', 'Senior Software Engineer', 145000, 'BCOM1234A'],
            ['Sneha Patil', '9820123401', 'sneha.patil@gmail.com', 'Pune', 'Maharashtra', '411045', 'female', 'married', 'Salaried', 'Wipro Technologies', 'Project Manager', 132000, 'AKPPD1234B'],
            ['Imran Khan', '9820123402', 'imran.khan@outlook.com', 'Mumbai', 'Maharashtra', '400008', 'male', 'single', 'Business Owner', 'Khan Exports', 'Proprietor', 240000, 'AJTPK5678C'],
            ['Meera Iyer', '9820123403', 'meera.iyer@gmail.com', 'Thane', 'Maharashtra', '400604', 'female', 'married', 'Professional', 'Self Employed', 'Architect', 185000, 'AABCI9012D'],
            ['Aditya Kulkarni', '9820123404', 'aditya.k@yahoo.com', 'Nashik', 'Maharashtra', '422009', 'male', 'single', 'Salaried', 'Persistent Systems', 'Tech Lead', 118000, 'ABCPK4567E'],
            ['Fatima Sheikh', '9820123405', 'fatima.sheikh@gmail.com', 'Mumbai', 'Maharashtra', '400053', 'female', 'married', 'Self Employed', 'Sheikh Boutique', 'Owner', 96000, 'AACPS7890F'],
            ['Rahul Deshpande', '9820123406', 'rahul.d@rediffmail.com', 'Navi Mumbai', 'Maharashtra', '410210', 'male', 'married', 'Salaried', 'TCS', 'Delivery Manager', 210000, 'ADFPD2345G'],
            ['Pooja Rane', '9820123407', 'pooja.rane@gmail.com', 'Ratnagiri', 'Maharashtra', '415612', 'female', 'single', 'Business Owner', 'Rane Agro Traders', 'Partner', 78000, 'AEFPR6789H'],
            ['Nikhil Bhatia', '9820123408', 'nikhil.bhatia@gmail.com', 'Nagpur', 'Maharashtra', '440010', 'male', 'married', 'Salaried', 'HCL Tech', 'Engineering Manager', 168000, 'AGHPB3456J'],
            ['Sanya Kapoor', '9820123409', 'sanya.kapoor@gmail.com', 'Mumbai', 'Maharashtra', '400050', 'female', 'single', 'Professional', 'Kapoor & Associates', 'Chartered Accountant', 195000, 'AHJPK7890K'],
            ['Vivek Nair', '9820123410', 'vivek.nair@gmail.com', 'Kochi', 'Kerala', '682024', 'male', 'married', 'Salaried', 'Cognizant', 'Program Manager', 225000, 'AIJPN2345L'],
            ['Ritu Agarwal', '9820123411', 'ritu.agarwal@gmail.com', 'Mumbai', 'Maharashtra', '400061', 'female', 'married', 'Business Owner', 'Agarwal Textiles', 'Director', 156000, 'AJKPA6789M'],
            ['Karan Malhotra', '9820123412', 'karan.malhotra@gmail.com', 'Pune', 'Maharashtra', '411001', 'male', 'single', 'Salaried', 'Barclays', 'AVP', 189000, 'AKLPM3456N'],
            ['Tanvi Bhatt', '9820123413', 'tanvi.bhatt@gmail.com', 'Vadodara', 'Gujarat', '390007', 'female', 'married', 'Professional', 'Self Employed', 'Physician', 162000, 'ALMPB7890P'],
            ['Arjun Reddy', '9820123414', 'arjun.reddy@gmail.com', 'Hyderabad', 'Telangana', '500081', 'male', 'married', 'Business Owner', 'Reddy Constructions', 'Managing Director', 285000, 'AMNPR2345Q'],
            ['Divya Menon', '9820123415', 'divya.menon@gmail.com', 'Bengaluru', 'Karnataka', '560037', 'female', 'single', 'Salaried', 'Flipkart', 'Senior Manager', 176000, 'ANOPM6789R'],
            ['Sahil Gupta', '9820123416', 'sahil.gupta@gmail.com', 'Delhi', 'Delhi', '110019', 'male', 'married', 'Self Employed', 'Gupta Electronics', 'Proprietor', 138000, 'AOPPG3456S'],
            ['Neelam Chavan', '9820123417', 'neelam.chavan@gmail.com', 'Aurangabad', 'Maharashtra', '431001', 'female', 'married', 'Salaried', 'Bajaj Auto', 'HR Manager', 112000, 'APQPC7890T'],
            ['Yash Thakur', '9820123418', 'yash.thakur@gmail.com', 'Indore', 'Madhya Pradesh', '452010', 'male', 'single', 'Salaried', 'Infobeans', 'Tech Architect', 149000, 'AQRPT2345U'],
            ['Shreya Banerjee', '9820123419', 'shreya.banerjee@gmail.com', 'Kolkata', 'West Bengal', '700091', 'female', 'married', 'Professional', 'Self Employed', 'Consultant', 205000, 'ARSPB6789V'],
        ];

        $employmentTypes = \App\Models\EmploymentType::query()->pluck('id', 'name')->all();

        foreach ($people as $index => $row) {
            [$name, $mobile, $email, $city, $state, $pincode, $gender, $marital, $employment, $company, $designation, $income, $pan] = $row;

            $customer = Customer::query()->create([
                'customer_code' => $this->codes->customer(),
                'name' => $name,
                'mobile' => $mobile,
                'email' => $email,
                'date_of_birth' => now()->subYears(random_int(27, 52))->subDays(random_int(1, 300))->toDateString(),
                'gender' => $gender,
                'marital_status' => $marital,
                'father_or_spouse_name' => (($gender === 'male') ? 'Mr. ' : 'Mrs. ').Str::of($name)->explode(' ')->last(),
                'pan_number' => $pan,
                'aadhaar_number' => (string) random_int(200000000000, 999999999999),
                'customer_type' => 'individual',
                'occupation' => $employment,
                'city' => $city,
                'state' => $state,
                'pincode' => $pincode,
                'address' => random_int(1, 40).', '.collect(['Sunrise', 'Green Meadows', 'Silver Oak', 'Palm Grove', 'Lake View'])->random().' Society, '.$city,
                'employment_type_id' => $employmentTypes[$employment] ?? null,
                'company_name' => $company,
                'designation' => $designation,
                'monthly_income' => $income,
                'annual_income' => $income * 12,
                'work_experience_years' => random_int(3, 18),
                'office_address' => $company.', '.$city,
                'assigned_employee_id' => $this->employees[$index % count($this->employees)]->id,
                'created_by' => $this->employees[$index % count($this->employees)]->id,
                'status' => 'active',
                'kyc_status' => ['pending', 'in_progress', 'verified', 'verified'][$index % 4],
                'created_at' => now()->subDays(random_int(5, 240)),
            ]);

            $customer->addresses()->create([
                'address_type' => 'residential',
                'address_line' => $customer->address,
                'city' => $city, 'state' => $state, 'pincode' => $pincode, 'is_primary' => true,
            ]);

            $customer->professionalDetails()->create([
                'employment_type_id' => $customer->employment_type_id,
                'company_name' => $company,
                'designation' => $designation,
                'monthly_income' => $income,
                'annual_income' => $income * 12,
                'work_experience_years' => $customer->work_experience_years,
                'office_address' => $customer->office_address,
                'is_current' => true,
            ]);

            $this->customerPool[] = $customer;
        }
    }

    protected function seedLeadsAndApplications(): void
    {
        $loans = Product::query()->where('slug', Product::LOANS)->firstOrFail();
        $insurance = Product::query()->where('slug', Product::INSURANCE)->firstOrFail();

        $loanCategories = ProductCategory::query()->where('product_id', $loans->id)->with('subcategories')->get();
        $insuranceCategories = ProductCategory::query()->where('product_id', $insurance->id)->with('subcategories')->get();

        $leadStatuses = LeadStatus::query()->pluck('id', 'slug');
        $applicationStatuses = ApplicationStatus::query()->pluck('id', 'slug');
        $sourceIds = array_values($this->sources);
        $leadStatusFlow = ['draft', 'new', 'verified', 'lender-selected', 'application-created', 'under-review', 'approved', 'disbursed', 'rejected', 'closed'];

        $total = 42;

        for ($i = 0; $i < $total; $i++) {
            $customer = $this->customerPool[$i % count($this->customerPool)];
            $isInsurance = $i % 7 === 5;
            $product = $isInsurance ? $insurance : $loans;
            /** @var ProductCategory $category */
            $category = ($isInsurance ? $insuranceCategories : $loanCategories)->random();
            $subcategory = $category->subcategories->random();
            $owner = $this->employees[$i % count($this->employees)];
            $createdAt = now()->subDays(random_int(1, 165))->subMinutes(random_int(0, 600));
            $stage = $leadStatusFlow[$i % count($leadStatusFlow)];

            if ($isInsurance && in_array($stage, ['lender-selected', 'disbursement-pending'], true)) {
                $stage = 'under-review';
            }

            $amount = (float) random_int(
                (int) max(25000, (float) ($category->min_amount ?? 25000)) / 1000,
                (int) min(5000000, (float) ($category->max_amount ?? 5000000)) / 1000
            ) * 1000;

            $lead = Lead::query()->create([
                'lead_code' => $this->codes->lead(),
                'customer_id' => $customer->id,
                'product_id' => $product->id,
                'product_category_id' => $category->id,
                'product_subcategory_id' => $subcategory->id,
                'lead_source_id' => $sourceIds[array_rand($sourceIds)],
                'lead_status_id' => $leadStatuses[$stage] ?? $leadStatuses['new'],
                'employment_type_id' => $customer->employment_type_id,
                'assigned_to' => $owner->id,
                'created_by' => $this->employees[($i + 1) % count($this->employees)]->id,
                'lead_type' => ['fresh', 'fresh', 'existing', 'priority'][$i % 4],
                'customer_type' => $i % 3 === 0 ? 'existing' : 'new',
                'preferred_contact_method' => ['call', 'whatsapp', 'email', 'sms'][$i % 4],
                'preferred_contact_time' => ['Morning (9-12)', 'Afternoon (12-4)', 'Evening (4-8)'][$i % 3],
                'priority' => (int) [1, 2, 2, 3][$i % 4],
                'loan_amount' => $amount,
                'tenure_months' => [$category->min_tenure_months ?? 12, 24, 36, 60, 84][$i % 5],
                'preferred_bank' => ['Any', 'HDFC Bank', 'ICICI Bank', 'Axis Bank'][$i % 4],
                'monthly_income' => $customer->monthly_income,
                'existing_emi' => $i % 3 === 0 ? random_int(8, 42) * 1000 : null,
                'credit_score' => random_int(690, 810),
                'is_otp_verified' => $stage !== 'draft',
                'otp_verified_at' => $stage !== 'draft' ? $createdAt->copy()->addMinutes(12) : null,
                'current_step' => $stage === 'draft' ? random_int(2, 6) : 10,
                'is_draft' => $stage === 'draft',
                'is_converted' => in_array($stage, ['application-created', 'under-review', 'approved', 'disbursed', 'closed'], true),
                'status' => $stage,
                'verification_status' => in_array($stage, ['draft', 'new'], true) ? 'pending' : 'verified',
                'expected_commission' => round($amount * 0.008, 2),
                'converted_at' => in_array($stage, ['application-created', 'under-review', 'approved', 'disbursed', 'closed'], true) ? $createdAt->copy()->addDays(3) : null,
                'last_activity_at' => $createdAt->copy()->addDays(random_int(1, 9))->min(now()->subHours(random_int(1, 20))),
            ]);

            $lead->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt->copy()->addDays(2)->min(now())])->saveQuietly();

            $this->seedLeadTimeline($lead, $stage, $createdAt);
            $this->attachLenders($lead, $category, $stage);

            if (in_array($stage, ['application-created', 'under-review', 'approved', 'disbursed', 'rejected', 'closed'], true)) {
                $this->seedApplication($lead, $category, $stage, $createdAt, $applicationStatuses, $isInsurance);
            }

            if ($i % 5 === 0) {
                Remark::query()->create([
                    'remarkable_type' => Lead::class,
                    'remarkable_id' => $lead->id,
                    'body' => 'Customer called - share lender options after verification.',
                    'type' => 'comment',
                    'created_by' => $owner->id,
                ]);
            }
        }
    }

    protected function seedLeadTimeline(Lead $lead, string $stage, Carbon $createdAt): void
    {
        $order = ['new', 'verified', 'lender-selected', 'application-created', 'under-review', 'approved', 'disbursed'];
        $statuses = LeadStatus::query()->pluck('id', 'slug');
        $history = [
            ['Lead Created', 'completed', 'Lead captured in the system', 'draft'],
        ];

        $offset = 0;
        $previous = $statuses['draft'] ?? null;

        foreach ($order as $slug) {
            if ($slug === 'new') {
                continue;
            }

            $reachable = array_search($stage, $order, true);
            $position = array_search($slug, $order, true);

            if ($stage === 'draft') {
                break;
            }

            if ($position > $reachable) {
                break;
            }

            $offset += random_int(4, 40);
            $state = $position === $reachable ? 'current' : 'completed';

            $history[] = [
                LeadStatus::query()->where('slug', $slug)->value('name'),
                $state,
                $slug === 'disbursed' ? 'Amount credited to customer account' : null,
                $slug,
            ];
        }

        $timestamp = $createdAt->copy();

        foreach ($history as $index => [$label, $state, $note, $slug]) {
            $timestamp = $timestamp->copy()->addHours($index === 0 ? 0 : random_int(6, 60));

            $lead->statusHistories()->create([
                'lead_status_id' => $statuses[$slug] ?? null,
                'from_status_id' => $index === 0 ? null : $previous,
                'stage' => $label,
                'state' => $index === 0 ? 'completed' : $state,
                'note' => $note,
                'changed_by' => $lead->created_by,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);

            $previous = $statuses[$slug] ?? $previous;
        }
    }

    protected function attachLenders(Lead $lead, ProductCategory $category, string $stage): void
    {
        if (in_array($stage, ['draft', 'new'], true)) {
            return;
        }

        $products = LenderProduct::query()
            ->where('product_category_id', $category->id)
            ->with('lender')
            ->orderBy('roi')
            ->limit(random_int(2, 4))
            ->get();

        $amount = (float) $lead->loan_amount;
        $tenure = (int) ($lead->tenure_months ?: 36);

        foreach ($products as $index => $product) {
            $emi = $product->calculateEmi($amount, $tenure) ?: null;

            LoanLender::query()->create([
                'lead_id' => $lead->id,
                'lender_id' => $product->lender_id,
                'lender_product_id' => $product->id,
                'loan_amount' => $amount,
                'tenure_months' => $tenure,
                'roi' => $product->roi,
                'apr' => $product->apr,
                'emi' => $emi,
                'processing_fee' => $product->processingFeeAmount($amount),
                'penal_charge' => $product->penal_charge,
                'other_charges' => random_int(0, 3) * 500,
                'required_documents' => $product->required_documents,
                'is_selected' => true,
                'is_primary' => $index === 0,
                'status' => 'selected',
                'created_by' => $lead->created_by,
            ]);
        }
    }

    protected function seedApplication(Lead $lead, ProductCategory $category, string $stage, Carbon $createdAt, $statuses, bool $isInsurance): void
    {
        $primary = $lead->lenders()->orderByDesc('is_primary')->first();

        $loan = LoanApplication::query()->create([
            'application_code' => $this->codes->application(),
            'lead_id' => $lead->id,
            'customer_id' => $lead->customer_id,
            'product_id' => $lead->product_id,
            'product_category_id' => $category->id,
            'product_subcategory_id' => $lead->product_subcategory_id,
            'application_status_id' => $statuses[$stage] ?? null,
            'status' => $stage,
            'loan_amount' => $lead->loan_amount,
            'tenure_months' => $lead->tenure_months,
            'roi' => $primary?->roi,
            'apr' => $primary?->apr,
            'emi' => $primary?->emi,
            'processing_fee' => $primary?->processing_fee,
            'sanctioned_amount' => in_array($stage, ['approved', 'disbursed', 'closed'], true) ? $lead->loan_amount : null,
            'disbursed_amount' => in_array($stage, ['disbursed', 'closed'], true) ? $lead->loan_amount : null,
            'sanction_date' => in_array($stage, ['approved', 'disbursed', 'closed'], true) ? $createdAt->copy()->addDays(9)->toDateString() : null,
            'primary_lender_id' => $primary?->lender_id,
            'preferred_bank' => $lead->preferred_bank,
            'employment_type' => $lead->employmentType?->name,
            'monthly_income' => $lead->monthly_income,
            'existing_emi' => $lead->existing_emi,
            'credit_score' => $lead->credit_score,
            'is_document_verified' => in_array($stage, ['approved', 'disbursed', 'closed'], true),
            'verification_status' => in_array($stage, ['approved', 'disbursed', 'closed'], true) ? 'verified' : 'in_progress',
            'rejection_reason' => $stage === 'rejected' ? 'Credit bureau score below lender cut-off.' : null,
            'assigned_to' => $lead->assigned_to,
            'created_by' => $lead->created_by,
            'submitted_at' => $createdAt->copy()->addDays(3),
            'approved_at' => in_array($stage, ['approved', 'disbursed', 'closed'], true) ? $createdAt->copy()->addDays(8)->min(now()) : null,
            'disbursed_at' => in_array($stage, ['disbursed', 'closed'], true) ? $createdAt->copy()->addDays(12)->min(now()) : null,
        ]);

        $loan->forceFill(['created_at' => $createdAt->copy()->addDays(3)])->saveQuietly();

        $lead->lenders()->update(['loan_application_id' => $loan->id]);

        // application timeline
        $flow = ['created', 'verified', 'under-review', 'approved', 'disbursed'];
        $reachable = match ($stage) {
            'application-created' => 'created',
            'under-review' => 'under-review',
            'approved' => 'approved',
            'disbursed', 'closed' => 'disbursed',
            'rejected' => 'rejected',
            default => null,
        };

        if ($reachable) {
            $timestamp = $createdAt->copy()->addDays(3);
            $allStatuses = ApplicationStatus::query()->pluck('id', 'slug');
            $previous = $allStatuses['created'] ?? null;

            $sequence = $reachable === 'rejected' ? ['created', 'verified', 'rejected'] : array_slice($flow, 0, array_search($reachable, $flow, true) + 1);

            foreach ($sequence as $index => $slug) {
                $timestamp = $timestamp->copy()->addHours($index === 0 ? 0 : random_int(12, 72));

                $loan->statusHistories()->create([
                    'application_type' => 'loan',
                    'application_id' => $loan->id,
                    'application_status_id' => $allStatuses[$slug] ?? null,
                    'from_status_id' => $index === 0 ? null : $previous,
                    'stage' => ApplicationStatus::query()->where('slug', $slug)->value('name') ?? $slug,
                    'state' => 'completed',
                    'note' => $slug === 'rejected' ? 'Rejected by credit team' : null,
                    'changed_by' => $lead->assigned_to,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);

                $previous = $allStatuses[$slug] ?? $previous;
            }
        }

        if (in_array($stage, ['disbursed', 'closed'], true)) {
            $this->seedDisbursement($loan, $createdAt);
        }
    }

    protected function seedDisbursement(LoanApplication $application, Carbon $createdAt): void
    {
        $amount = (float) $application->loan_amount;

        $disbursement = Disbursement::query()->create([
            'disbursement_code' => $this->codes->disbursement(),
            'loan_application_id' => $application->id,
            'customer_id' => $application->customer_id,
            'lender_id' => $application->primary_lender_id,
            'loan_lender_id' => $application->lenders()->where('is_primary', true)->value('id'),
            'approved_amount' => $amount,
            'disbursed_amount' => $amount,
            'disbursement_date' => $createdAt->copy()->addDays(12)->toDateString(),
            'reference_number' => 'UTR'.random_int(100000000, 999999999),
            'utr_number' => 'UTR'.random_int(100000000, 999999999),
            'bank_name' => 'HDFC Bank',
            'bank_account_number' => '50100'.random_int(100000, 999999),
            'bank_ifsc' => 'HDFC0000123',
            'processing_fee' => round($amount * 0.02, 2),
            'other_charges' => 1500,
            'status' => 'completed',
            'mode' => 'neft',
            'created_by' => $application->created_by,
            'approved_by' => $application->created_by,
            'completed_at' => $createdAt->copy()->addDays(12),
            'created_at' => $createdAt->copy()->addDays(12),
        ]);

        $disbursement->recalculateNet();
        $disbursement->saveQuietly();
    }

    protected function seedInvoicesAndPayments(): void
    {
        $methodIds = PaymentMethod::query()->pluck('id', 'code');
        $invoices = [];

        foreach (array_slice($this->customerPool, 0, 14) as $index => $customer) {
            $application = LoanApplication::query()->where('customer_id', $customer->id)->first();
            $status = ['draft', 'issued', 'partially_paid', 'paid', 'overdue', 'cancelled'][$index % 6];
            $invoiceDate = now()->subDays(random_int(5, 90));

            $invoice = Invoice::query()->create([
                'invoice_number' => $this->codes->invoice(),
                'customer_id' => $customer->id,
                'lead_id' => $application?->lead_id,
                'loan_application_id' => $application?->id,
                'title' => 'Professional & processing services',
                'invoice_date' => $invoiceDate->toDateString(),
                'due_date' => $invoiceDate->copy()->addDays(15)->toDateString(),
                'discount' => $index % 4 === 0 ? 2000 : 0,
                'tax_rate' => 18,
                'status' => $status,
                'notes' => 'Thank you for your business.',
                'terms' => "1. Payment due within 15 days.\n2. Quote the invoice number with your payment.",
                'created_by' => $this->employees[$index % count($this->employees)]->id,
                'issued_at' => $status !== 'draft' ? $invoiceDate : null,
                'issued_by' => $status !== 'draft' ? $this->employees[$index % count($this->employees)]->id : null,
                'created_at' => $invoiceDate,
            ]);

            $lines = [
                ['Loan processing & documentation fee', 1, round((float) ($application?->loan_amount ?? 500000) * 0.01, 2)],
                ['Advisory & credit assessment', 1, 7500],
                ['GST filing & compliance support', 2, 2500],
            ];

            foreach ($lines as $sort => [$description, $qty, $price]) {
                $item = $invoice->items()->create([
                    'description' => $description,
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'tax_rate' => 18,
                    'sort_order' => $sort,
                ]);
                $item->computeLineTotal();
                $item->save();
            }

            $invoice->recalculate();
            $invoice->forceFill(['status' => $status])->save();

            if (in_array($status, ['paid', 'partially_paid'], true)) {
                $amount = $status === 'paid' ? (float) $invoice->total : round((float) $invoice->total * 0.45, 2);

                Payment::query()->create([
                    'payment_code' => $this->codes->payment(),
                    'invoice_id' => $invoice->id,
                    'customer_id' => $customer->id,
                    'loan_application_id' => $application?->id,
                    'amount' => $amount,
                    'payment_method_id' => $methodIds[['upi', 'bank-transfer', 'cheque', 'cash'][$index % 4]] ?? null,
                    'transaction_id' => 'TXN'.random_int(1000000000, 9999999999),
                    'payment_date' => $invoiceDate->copy()->addDays(random_int(2, 20))->toDateString(),
                    'received_by' => $this->employees[$index % count($this->employees)]->id,
                    'status' => 'completed',
                    'bank_name' => 'HDFC Bank',
                    'created_by' => $this->employees[$index % count($this->employees)]->id,
                ]);

                $invoice->refresh();
                $invoice->recalculate();
                $invoice->save();
            }

            $invoices[] = $invoice;
        }
    }

    protected function seedAttendance(): void
    {
        $employees = Employee::query()->get();
        $statuses = ['present', 'present', 'present', 'present', 'late', 'half_day', 'leave', 'absent'];

        foreach ($employees as $employee) {
            for ($day = 40; $day >= 0; $day--) {
                $date = now()->subDays($day);

                if ($date->isSunday()) {
                    continue;
                }

                if (random_int(1, 100) > 88) {
                    continue;
                }

                $status = $statuses[array_rand($statuses)];
                $checkIn = $date->copy()->setTime(9, 30)->addMinutes(random_int(-20, 75));
                $checkOut = $checkIn->copy()->addHours(random_int(7, 10))->addMinutes(random_int(0, 55));
                $workedMinutes = $status === 'half_day' ? random_int(200, 280) : (int) $checkIn->diffInMinutes($checkOut);

                Attendance::query()->create([
                    'employee_id' => $employee->id,
                    'attendance_date' => $date->toDateString(),
                    'check_in_at' => in_array($status, ['absent', 'leave'], true) ? null : $checkIn,
                    'check_out_at' => in_array($status, ['absent', 'leave'], true) ? null : $checkOut,
                    'worked_minutes' => in_array($status, ['absent', 'leave'], true) ? 0 : $workedMinutes,
                    'late_minutes' => $status === 'late' ? random_int(16, 65) : 0,
                    'status' => $status,
                    'work_mode' => random_int(0, 3) === 0 ? 'remote' : 'office',
                    'remarks' => $status === 'leave' ? 'Approved casual leave' : null,
                ]);
            }
        }

        foreach ($employees as $employee) {
            Leave::query()->create([
                'employee_id' => $employee->id,
                'from_date' => now()->addDays(random_int(3, 20))->toDateString(),
                'to_date' => now()->addDays(random_int(21, 25))->toDateString(),
                'leave_type' => ['casual', 'sick', 'earned'][$employee->id % 3],
                'days' => 1,
                'reason' => 'Personal work',
                'status' => ['pending', 'approved', 'rejected'][$employee->id % 3],
                'approved_by' => User::query()->whereHas('role', fn ($q) => $q->where('slug', 'admin'))->value('id'),
                'approved_at' => now(),
            ]);
        }
    }
}
