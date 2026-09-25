<?php

use App\Models\ApplicationStatus;
use App\Models\Department;
use App\Models\Designation;
use App\Models\DocumentType;
use App\Models\EmploymentType;
use App\Models\InsuranceType;
use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\Lender;
use App\Models\LoanStatus;
use App\Models\NotificationType;
use App\Models\PaymentMethod;
use App\Models\ProductCategory;
use App\Models\ProductSubcategory;
use App\Models\Setting;

return [

    /*
    |--------------------------------------------------------------------------
    | Master Management registry
    |--------------------------------------------------------------------------
    |
    | The Master Management module is entirely registry driven: adding a new
    | master means adding an entry here - no controller, route or view change
    | is required.
    |
    */
    'masters' => [

        'lead-sources' => [
            'label' => 'Lead Sources',
            'singular' => 'Lead Source',
            'icon' => 'bi-broadcast',
            'model' => LeadSource::class,
            'columns' => [
                'name' => 'Source Name',
                'code' => 'Code',
                'description' => 'Description',
            ],
            'searchable' => ['name', 'code', 'description'],
            'rules' => [
                'code' => ['nullable', 'string', 'max:40', 'unique:lead_sources,code'],
            ],
        ],

        'lead-statuses' => [
            'label' => 'Lead Statuses',
            'singular' => 'Lead Status',
            'icon' => 'bi-diagram-3',
            'model' => LeadStatus::class,
            'columns' => [
                'name' => 'Status',
                'slug' => 'Slug',
                'stage_order' => 'Stage',
                'color' => 'Colour',
            ],
            'searchable' => ['name', 'slug'],
            'rules' => [
                'slug' => ['nullable', 'string', 'max:60', 'unique:lead_statuses,slug'],
                'stage_order' => ['nullable', 'integer', 'min:1'],
            ],
        ],

        'application-statuses' => [
            'label' => 'Application Statuses',
            'singular' => 'Application Status',
            'icon' => 'bi-list-check',
            'model' => ApplicationStatus::class,
            'columns' => [
                'name' => 'Status',
                'slug' => 'Slug',
                'stage_order' => 'Stage',
                'color' => 'Colour',
            ],
            'searchable' => ['name', 'slug'],
            'rules' => [
                'slug' => ['nullable', 'string', 'max:60', 'unique:application_statuses,slug'],
                'stage_order' => ['nullable', 'integer', 'min:1'],
            ],
        ],

        'loan-statuses' => [
            'label' => 'Loan Statuses',
            'singular' => 'Loan Status',
            'icon' => 'bi-cash-stack',
            'model' => LoanStatus::class,
            'columns' => ['name' => 'Status', 'slug' => 'Slug', 'color' => 'Colour'],
            'searchable' => ['name', 'slug'],
            'rules' => [
                'slug' => ['nullable', 'string', 'max:60', 'unique:loan_statuses,slug'],
            ],
        ],

        'payment-methods' => [
            'label' => 'Payment Methods',
            'singular' => 'Payment Method',
            'icon' => 'bi-credit-card',
            'model' => PaymentMethod::class,
            'columns' => ['name' => 'Method', 'code' => 'Code', 'icon' => 'Icon'],
            'searchable' => ['name', 'code'],
            'rules' => [
                'code' => ['nullable', 'string', 'max:40', 'unique:payment_methods,code'],
            ],
        ],

        'employment-types' => [
            'label' => 'Employment Types',
            'singular' => 'Employment Type',
            'icon' => 'bi-briefcase',
            'model' => EmploymentType::class,
            'columns' => ['name' => 'Type', 'code' => 'Code'],
            'searchable' => ['name', 'code'],
            'rules' => [
                'code' => ['nullable', 'string', 'max:40', 'unique:employment_types,code'],
            ],
        ],

        'document-types' => [
            'label' => 'Document Types',
            'singular' => 'Document Type',
            'icon' => 'bi-file-earmark-text',
            'model' => DocumentType::class,
            'columns' => [
                'name' => 'Document',
                'code' => 'Code',
                'applies_to' => 'Applies To',
                'allowed_extensions' => 'Extensions',
            ],
            'searchable' => ['name', 'code'],
            'rules' => [
                'code' => ['nullable', 'string', 'max:60', 'unique:document_types,code'],
                'applies_to' => ['nullable', 'in:all,loan,insurance,finance,card,real_estate'],
                'allowed_extensions' => ['nullable', 'string', 'max:120'],
                'max_size_kb' => ['nullable', 'integer', 'min:1'],
            ],
        ],

        'notification-types' => [
            'label' => 'Notification Types',
            'singular' => 'Notification Type',
            'icon' => 'bi-bell',
            'model' => NotificationType::class,
            'columns' => ['name' => 'Notification', 'slug' => 'Slug', 'module' => 'Module', 'color' => 'Colour'],
            'searchable' => ['name', 'slug', 'module'],
            'rules' => [
                'slug' => ['nullable', 'string', 'max:80', 'unique:notification_types,slug'],
                'module' => ['nullable', 'string', 'max:60'],
                'channel' => ['nullable', 'in:database,mail,sms,push'],
            ],
        ],

        'departments' => [
            'label' => 'Departments',
            'singular' => 'Department',
            'icon' => 'bi-building',
            'model' => Department::class,
            'columns' => ['name' => 'Department', 'code' => 'Code', 'description' => 'Description'],
            'searchable' => ['name', 'code'],
            'rules' => [
                'code' => ['nullable', 'string', 'max:40', 'unique:departments,code'],
            ],
        ],

        'designations' => [
            'label' => 'Designations',
            'singular' => 'Designation',
            'icon' => 'bi-person-badge',
            'model' => Designation::class,
            'columns' => ['name' => 'Designation', 'code' => 'Code', 'level' => 'Level'],
            'searchable' => ['name', 'code'],
            'rules' => [
                'code' => ['nullable', 'string', 'max:40', 'unique:designations,code'],
                'department_id' => ['nullable', 'exists:departments,id'],
                'level' => ['nullable', 'string', 'max:10'],
            ],
        ],

        'product-categories' => [
            'label' => 'Product Categories',
            'singular' => 'Product Category',
            'icon' => 'bi-grid-3x3-gap',
            'model' => ProductCategory::class,
            'columns' => [
                'name' => 'Category',
                'product_id' => 'Product',
                'code' => 'Code',
                'default_roi' => 'Default ROI %',
            ],
            'searchable' => ['name', 'code'],
            'rules' => [
                'product_id' => ['required', 'exists:products,id'],
                'code' => ['nullable', 'string', 'max:40'],
                'min_amount' => ['nullable', 'numeric', 'min:0'],
                'max_amount' => ['nullable', 'numeric', 'min:0'],
                'default_roi' => ['nullable', 'numeric', 'between:0,60'],
                'min_tenure_months' => ['nullable', 'integer', 'min:1'],
                'max_tenure_months' => ['nullable', 'integer', 'min:1'],
            ],
        ],

        'product-subcategories' => [
            'label' => 'Product Sub-Categories',
            'singular' => 'Product Sub-Category',
            'icon' => 'bi-list-nested',
            'model' => ProductSubcategory::class,
            'columns' => [
                'name' => 'Sub-Category',
                'product_category_id' => 'Parent Category',
                'code' => 'Code',
            ],
            'searchable' => ['name', 'code'],
            'rules' => [
                'product_category_id' => ['required', 'exists:product_categories,id'],
                'code' => ['nullable', 'string', 'max:40'],
            ],
        ],

        'lenders' => [
            'label' => 'Lenders & Insurers',
            'singular' => 'Lender',
            'icon' => 'bi-bank',
            'model' => Lender::class,
            'columns' => [
                'name' => 'Lender',
                'code' => 'Code',
                'lender_type' => 'Type',
                'online_status' => 'Mode',
                'status' => 'Status',
            ],
            'searchable' => ['name', 'code', 'city'],
            'rules' => [
                'code' => ['nullable', 'string', 'max:40', 'unique:lenders,code'],
                'lender_type' => ['nullable', 'in:bank,nbfc,hfc,insurer,fintech,other'],
                'online_status' => ['nullable', 'in:online,offline'],
                'contact_person' => ['nullable', 'string', 'max:120'],
                'contact_number' => ['nullable', 'string', 'max:30'],
                'contact_email' => ['nullable', 'email', 'max:150'],
                'website' => ['nullable', 'url', 'max:180'],
                'processing_time_days' => ['nullable', 'integer', 'min:0'],
                'min_ticket_size' => ['nullable', 'numeric', 'min:0'],
                'max_ticket_size' => ['nullable', 'numeric', 'min:0'],
                'city' => ['nullable', 'string', 'max:80'],
                'status' => ['nullable', 'in:active,inactive,suspended'],
            ],
        ],

        'insurance-types' => [
            'label' => 'Insurance Types',
            'singular' => 'Insurance Type',
            'icon' => 'bi-umbrella',
            'model' => InsuranceType::class,
            'columns' => ['name' => 'Insurance Type', 'code' => 'Code', 'description' => 'Description'],
            'searchable' => ['name', 'code'],
            'rules' => [
                'code' => ['nullable', 'string', 'max:40'],
            ],
        ],

        'settings' => [
            'label' => 'Settings',
            'singular' => 'Setting',
            'icon' => 'bi-sliders',
            'model' => Setting::class,
            'columns' => ['label' => 'Setting', 'key' => 'Key', 'group' => 'Group', 'value' => 'Value'],
            'searchable' => ['label', 'key', 'group'],
            'rules' => [
                'key' => ['required', 'string', 'max:80'],
                'group' => ['nullable', 'string', 'max:60'],
                'type' => ['nullable', 'in:text,number,boolean,json,date'],
                'label' => ['nullable', 'string', 'max:150'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Product theming
    |--------------------------------------------------------------------------
    |
    | Fallback presentation tokens keyed by product slug. Values seeded in the
    | products table always win; this only keeps the UI stable if a product is
    | created before its styling is configured.
    |
    */
    'product_themes' => [
        'loans' => ['color' => 'success', 'icon' => 'bi-cash-coin'],
        'insurance' => ['color' => 'warning', 'icon' => 'bi-umbrella'],
        'cards' => ['color' => 'info', 'icon' => 'bi-credit-card-2-front'],
        'real-estate' => ['color' => 'danger', 'icon' => 'bi-house-door'],
    ],

    'uploads' => [
        'disk' => 'local',
        'max_size_kb' => 5120,
        'mimes' => ['pdf', 'jpg', 'jpeg', 'png'],
        'allowed_extensions' => ['pdf', 'jpg', 'jpeg', 'png'],
        'path' => 'documents',
    ],

    'pagination' => [
        'per_page' => 25,
        'options' => [10, 25, 50, 100],
    ],

    'reports' => [
        'lead-summary' => 'Lead Summary Report',
        'lead-conversion' => 'Lead Conversion Report',
        'customer' => 'Customer Report',
        'loan-application' => 'Loan Application Report',
        'loan-disbursement' => 'Disbursement Report',
        'insurance' => 'Insurance Placement Report',
        'invoice' => 'Invoice & Billing Report',
        'payment-collection' => 'Payment Collection Report',
        'employee-performance' => 'Employee Performance Report',
        'attendance' => 'Attendance Report',
    ],

    'attendance' => [
        'late_grace_minutes' => 15,
        'half_day_minutes' => 300,
    ],

];
