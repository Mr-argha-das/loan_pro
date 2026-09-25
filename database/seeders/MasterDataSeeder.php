<?php

namespace Database\Seeders;

use App\Models\ApplicationStatus;
use App\Models\DocumentType;
use App\Models\EmploymentType;
use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\LoanStatus;
use App\Models\NotificationType;
use App\Models\PaymentMethod;
use App\Models\Setting;
use App\Services\NotificationService;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public const LEAD_STATUSES = [
        ['Draft', 'draft', 'secondary', 1, true, false, false],
        ['New', 'new', 'primary', 2, false, false, false],
        ['Verified', 'verified', 'success', 3, false, false, false],
        ['Lender Selected', 'lender-selected', 'primary', 4, false, false, false],
        ['Application Created', 'application-created', 'info', 5, false, false, false],
        ['Under Review', 'under-review', 'warning', 6, false, false, false],
        ['Approved', 'approved', 'success', 7, false, true, false],
        ['Rejected', 'rejected', 'danger', 8, false, false, true],
        ['Disbursement Pending', 'disbursement-pending', 'warning', 9, false, false, false],
        ['Disbursed', 'disbursed', 'success', 10, false, true, false],
        ['Closed', 'closed', 'dark', 11, false, true, false],
        ['Cancelled', 'cancelled', 'secondary', 12, false, false, true],
    ];

    public const APPLICATION_STATUSES = [
        ['Draft', 'draft', 'secondary', 'file-earmark', 1, true],
        ['Created', 'created', 'info', 'clipboard-plus', 2, false],
        ['Verified', 'verified', 'success', 'patch-check', 3, false],
        ['Lender Selected', 'lender-selected', 'primary', 'bank', 4, false],
        ['Application Created', 'application-created', 'info', 'clipboard-data', 5, false],
        ['Under Review', 'under-review', 'warning', 'hourglass-split', 6, false],
        ['Approved', 'approved', 'success', 'check-circle', 7, false],
        ['Rejected', 'rejected', 'danger', 'x-circle', 8, true],
        ['Disbursement Pending', 'disbursement-pending', 'warning', 'hourglass-bottom', 9, false],
        ['Disbursed', 'disbursed', 'success', 'cash-stack', 10, false],
        ['Closed', 'closed', 'dark', 'lock', 11, true],
        ['Cancelled', 'cancelled', 'secondary', 'slash-circle', 12, true],
    ];

    public const DOCUMENT_TYPES = [
        ['Identity Proof', 'identity_proof', 'all', true, false],
        ['Address Proof', 'address_proof', 'all', true, false],
        ['PAN Card', 'pan_card', 'all', true, false],
        ['Income Proof', 'income_proof', 'loan', true, false],
        ['Bank Statement', 'bank_statement', 'loan', true, false],
        ['Passport Size Photo', 'passport_photo', 'all', true, false],
        ['GST Returns', 'gst_returns', 'loan', false, false],
        ['ITR', 'itr', 'loan', false, false],
        ['Property Documents', 'property_documents', 'loan', false, false],
        ['Business Proof', 'business_proof', 'loan', false, false],
        ['Salary Slips', 'salary_slips', 'loan', false, false],
        ['Cheque', 'cheque', 'finance', false, true],
        ['Policy Document', 'policy_document', 'insurance', true, true],
        ['Medical Report', 'medical_report', 'insurance', false, false],
        ['Vehicle Invoice', 'vehicle_invoice', 'insurance', false, false],
        ['Loan Agreement', 'loan_agreement', 'loan', false, true],
        ['Sanction Letter', 'sanction_letter', 'loan', false, false],
    ];

    public function run(): void
    {
        $this->leadSources();
        $this->leadStatuses();
        $this->applicationStatuses();
        $this->loanStatuses();
        $this->paymentMethods();
        $this->employmentTypes();
        $this->documentTypes();
        $this->notificationTypes();
        $this->settings();
    }

    protected function leadSources(): void
    {
        $sources = [
            ['Freelancer', 'freelancer', 'primary', 'Freelancer sourced lead'],
            ['Website', 'website', 'info', 'Inbound website enquiry'],
            ['Referral', 'referral', 'success', 'Customer or partner referral'],
            ['Walk-in', 'walk-in', 'warning', 'Walk-in at branch'],
            ['Partner', 'partner', 'dark', 'Channel partner lead'],
            ['Other', 'other', 'secondary', 'Any other source'],
            ['Social Media', 'social-media', 'info', 'Social campaigns'],
            ['Call Centre', 'call-centre', 'primary', 'Telecalling team'],
        ];

        foreach ($sources as $index => [$name, $code, $color, $description]) {
            LeadSource::query()->updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'color' => $color, 'description' => $description, 'is_active' => true, 'sort_order' => $index + 1]
            );
        }
    }

    protected function leadStatuses(): void
    {
        foreach (self::LEAD_STATUSES as $index => [$name, $slug, $color, $order, $default, $won, $lost]) {
            LeadStatus::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'color' => $color,
                    'stage_order' => $order,
                    'is_default' => $default,
                    'is_won' => $won,
                    'is_lost' => $lost,
                    'is_active' => true,
                    'description' => $name.' stage of the lead journey.',
                ]
            );
        }
    }

    protected function applicationStatuses(): void
    {
        foreach (self::APPLICATION_STATUSES as [$name, $slug, $color, $icon, $order, $closed]) {
            ApplicationStatus::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'color' => $color,
                    'icon' => $icon,
                    'stage_order' => $order,
                    'is_closed' => $closed,
                    'is_default' => $slug === 'draft',
                    'is_active' => true,
                ]
            );
        }
    }

    protected function loanStatuses(): void
    {
        $statuses = [
            ['Pending', 'pending', 'warning'],
            ['Sanctioned', 'sanctioned', 'primary'],
            ['Active', 'active', 'success'],
            ['Closed', 'closed', 'dark'],
            ['Written Off', 'written-off', 'danger'],
        ];

        foreach ($statuses as $index => [$name, $slug, $color]) {
            LoanStatus::query()->updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'color' => $color, 'stage_order' => $index + 1, 'is_active' => true]
            );
        }
    }

    protected function paymentMethods(): void
    {
        $methods = [
            ['Cash', 'cash', 'bi-cash', false],
            ['Bank Transfer', 'bank-transfer', 'bi-bank', true],
            ['UPI', 'upi', 'bi-phone', true],
            ['Card', 'card', 'bi-credit-card', true],
            ['Cheque', 'cheque', 'bi-file-earmark-text', true],
            ['Other', 'other', 'bi-three-dots', false],
        ];

        foreach ($methods as $index => [$name, $code, $icon, $requiresReference]) {
            PaymentMethod::query()->updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'icon' => $icon, 'requires_reference' => $requiresReference, 'is_active' => true, 'sort_order' => $index + 1]
            );
        }
    }

    protected function employmentTypes(): void
    {
        $types = [
            ['Salaried', 'salaried', true],
            ['Self Employed', 'self-employed', true],
            ['Business Owner', 'business-owner', true],
            ['Professional', 'professional', true],
            ['Retired', 'retired', false],
            ['Other', 'other', false],
        ];

        foreach ($types as $index => [$name, $code, $requiresCompany]) {
            EmploymentType::query()->updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'requires_company_details' => $requiresCompany, 'is_active' => true, 'sort_order' => $index + 1]
            );
        }
    }

    protected function documentTypes(): void
    {
        foreach (self::DOCUMENT_TYPES as $index => [$name, $code, $appliesTo, $required, $hasExpiry]) {
            DocumentType::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'applies_to' => $appliesTo,
                    'is_required_default' => $required,
                    'has_expiry' => $hasExpiry,
                    'allowed_extensions' => 'pdf,jpg,jpeg,png',
                    'max_size_kb' => 5120,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }
    }

    protected function notificationTypes(): void
    {
        foreach (NotificationService::TYPE_MAP as $slug => [$name, $icon, $color, $module]) {
            NotificationType::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'icon' => $icon,
                    'color' => $color,
                    'channel' => 'database',
                    'description' => $name.' events routed to the notification centre.',
                    'is_active' => true,
                ]
            );
        }
    }

    protected function settings(): void
    {
        $settings = [
            ['company', 'company_name', 'LoanPro Finance & Insurance', 'text', 'Company Name', 'Displayed on invoices, PDFs and the sidebar.'],
            ['company', 'company_tagline', 'Finance & Insurance', 'text', 'Tagline', null],
            ['company', 'company_email', 'care@loanpro.in', 'text', 'Support Email', null],
            ['company', 'company_phone', '+91 98200 11223', 'text', 'Support Phone', null],
            ['company', 'company_address', '3rd Floor, Fortune Business Park, Andheri East, Mumbai 400069', 'text', 'Registered Address', null],
            ['company', 'gst_number', '27ABCDE1234F1Z5', 'text', 'GST Number', null],
            ['finance', 'currency', 'INR', 'text', 'Currency', null],
            ['finance', 'default_tax_rate', '18', 'number', 'Default GST %', 'Applied to new invoices.'],
            ['finance', 'invoice_due_days', '15', 'number', 'Invoice Due Days', null],
            ['finance', 'invoice_prefix', 'INV', 'text', 'Invoice Prefix', null],
            ['leads', 'lead_prefix', 'LD', 'text', 'Lead ID Prefix', null],
            ['leads', 'otp_required', '1', 'boolean', 'Require OTP verification', null],
            ['leads', 'default_lead_status', 'new', 'text', 'Default Lead Status', null],
            ['leads', 'auto_assign_leads', '1', 'boolean', 'Auto assign new leads to creator', null],
            ['hr', 'office_start_time', '09:30', 'text', 'Office Start Time', null],
            ['hr', 'office_end_time', '18:30', 'text', 'Office End Time', null],
            ['hr', 'late_grace_minutes', '15', 'number', 'Late Grace (minutes)', null],
            ['hr', 'working_days', 'Mon,Tue,Wed,Thu,Fri,Sat', 'text', 'Working Days', null],
            ['notifications', 'notify_on_lead_assigned', '1', 'boolean', 'Notify on lead assignment', null],
            ['notifications', 'notify_on_status_change', '1', 'boolean', 'Notify on status change', null],
        ];

        foreach ($settings as $index => [$group, $key, $value, $type, $label, $description]) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                [
                    'group' => $group,
                    'value' => $value,
                    'type' => $type,
                    'label' => $label,
                    'description' => $description,
                    'sort_order' => $index + 1,
                ]
            );
        }
    }
}
