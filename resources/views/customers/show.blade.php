@extends('layouts.app')

@section('title', $customer->name)
@section('page-header', true)
@section('page-title', $customer->name)
@section('page-subtitle', $customer->customer_code.' · '.($customer->city ? $customer->city.', ' : '').($customer->occupation ?? 'Customer profile'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Customers</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $customer->customer_code }}</li>
@endsection

@section('page-actions')
    <div class="d-flex flex-wrap gap-2">
        @canPermission('leads.create')
            <a href="{{ route('leads.create', ['customer_id' => $customer->id]) }}" class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i> Create Lead</a>
        @endcanPermission
        @can('update', $customer)
            <a href="{{ route('customers.edit', $customer) }}" class="btn btn-light btn-sm"><i class="bi bi-pencil"></i> Edit</a>
        @endcan
        @can('delete', $customer)
            <form method="POST" action="{{ route('customers.destroy', $customer) }}" class="d-inline" data-confirm="Delete {{ $customer->name }}? Historical records are retained.">
                @csrf @method('DELETE')
                <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
            </form>
        @endcan
    </div>
@endsection

@section('content')
    <div class="lp-profile-head lp-card mb-4">
        <div class="lp-card__body">
            <div class="row g-4 align-items-center">
                <div class="col-lg-4">
                    <div class="d-flex align-items-center gap-3">
                        <span class="lp-avatar lp-avatar--xl">{{ $customer->initials() }}</span>
                        <div>
                            <div class="fw-semibold fs-5">{{ $customer->name }}</div>
                            <div class="text-muted small"><i class="bi bi-telephone me-1"></i>{{ $customer->mobile }}</div>
                            <div class="text-muted small"><i class="bi bi-envelope me-1"></i>{{ $customer->email ?? 'No email on record' }}</div>
                            <div class="d-flex flex-wrap gap-1 mt-2">
                                <x-status-badge :status="$customer->kyc_status" />
                                <x-status-badge :status="$customer->status" />
                                <span class="lp-badge bg-secondary-subtle text-secondary bg-opacity-10">{{ \App\Support\Format::titleCase($customer->customer_type) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="row g-3">
                        <div class="col-6 col-md-3">
                            <x-stat-card label="Leads" :value="$leads->count()" icon="bi-funnel" tone="primary" />
                        </div>
                        <div class="col-6 col-md-3">
                            <x-stat-card label="Applications" :value="$applications->count()" icon="bi-clipboard-data" tone="blue" />
                        </div>
                        <div class="col-6 col-md-3">
                            <x-stat-card label="Collected" :value="\App\Support\Format::compactInr($payments->sum('amount'))" icon="bi-cash-stack" tone="green" />
                        </div>
                        <div class="col-6 col-md-3">
                            <x-stat-card label="Outstanding" :value="\App\Support\Format::compactInr($invoices->sum(fn ($i) => (float) $i->balance_amount))" icon="bi-exclamation-circle" tone="orange" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="lp-divider"></div>

            <div class="row g-2 small">
                <div class="col-6 col-md-3"><span class="text-muted">PAN:</span> <span class="fw-semibold">{{ $customer->pan_number ?? '—' }}</span></div>
                <div class="col-6 col-md-3"><span class="text-muted">Aadhaar:</span> <span class="fw-semibold">{{ $customer->aadhaar_number ? \App\Support\Format::mask($customer->aadhaar_number) : '—' }}</span></div>
                <div class="col-6 col-md-3"><span class="text-muted">Monthly income:</span> <span class="fw-semibold">{{ \App\Support\Format::money($customer->monthly_income) }}</span></div>
                <div class="col-6 col-md-3"><span class="text-muted">Relationship manager:</span> <span class="fw-semibold">{{ $customer->assignedEmployee?->name ?? 'Unassigned' }}</span></div>
                <div class="col-6 col-md-3"><span class="text-muted">Customer since:</span> <span class="fw-semibold">{{ $customer->created_at?->format('d M Y') }}</span></div>
                <div class="col-6 col-md-3"><span class="text-muted">Last contacted:</span> <span class="fw-semibold">{{ $customer->last_contacted_at?->diffForHumans() ?? '—' }}</span></div>
                <div class="col-6 col-md-3"><span class="text-muted">Employment:</span> <span class="fw-semibold">{{ $customer->employmentType?->name ?? '—' }}</span></div>
                <div class="col-6 col-md-3"><span class="text-muted">Created by:</span> <span class="fw-semibold">{{ $customer->creator?->name ?? 'System' }}</span></div>
            </div>
        </div>
    </div>

    <x-tabs id="customer-tabs" :tabs="[
        'overview' => ['label' => 'Overview', 'icon' => 'bi-grid'],
        'personal' => ['label' => 'Personal', 'icon' => 'bi-person-vcard'],
        'professional' => ['label' => 'Professional', 'icon' => 'bi-briefcase'],
        'kyc' => ['label' => 'KYC', 'icon' => 'bi-patch-check'],
        'leads' => ['label' => 'Leads', 'icon' => 'bi-funnel', 'count' => $leads->count()],
        'applications' => ['label' => 'Applications', 'icon' => 'bi-clipboard-data', 'count' => $applications->count()],
        'loans' => ['label' => 'Loans', 'icon' => 'bi-cash-coin'],
        'insurance' => ['label' => 'Insurance', 'icon' => 'bi-shield-check', 'count' => $insurance->count()],
        'payments' => ['label' => 'Payments', 'icon' => 'bi-wallet2', 'count' => $payments->count()],
        'documents' => ['label' => 'Documents', 'icon' => 'bi-folder-check', 'count' => $documents->count()],
        'activity' => ['label' => 'Activity', 'icon' => 'bi-activity'],
    ]">

        <div class="tab-content lp-tab-content">
            {{-- ------------------------------------------------------- overview --}}
            <div class="tab-pane fade show active" id="customer-tabs-overview" role="tabpanel" aria-labelledby="customer-tabs-overview-tab">
                <div class="row g-4">
                    <div class="col-lg-8">
                        <x-card title="Engagement summary" icon="bi-graph-up-arrow">
                            <div class="row g-3">
                                <div class="col-sm-6 col-lg-3"><div class="lp-metric"><span>Total loan value</span><strong>{{ \App\Support\Format::compactInr($applications->sum(fn ($a) => (float) $a->loan_amount)) }}</strong></div></div>
                                <div class="col-sm-6 col-lg-3"><div class="lp-metric"><span>Sanctioned</span><strong>{{ \App\Support\Format::compactInr($applications->sum(fn ($a) => (float) $a->sanctioned_amount)) }}</strong></div></div>
                                <div class="col-sm-6 col-lg-3"><div class="lp-metric"><span>Disbursed</span><strong>{{ \App\Support\Format::compactInr($applications->sum(fn ($a) => (float) $a->disbursed_amount)) }}</strong></div></div>
                                <div class="col-sm-6 col-lg-3"><div class="lp-metric"><span>Invoices raised</span><strong>{{ $invoices->count() }}</strong></div></div>
                            </div>

                            <div class="table-responsive mt-4">
                                <table class="lp-table">
                                    <thead>
                                        <tr>
                                            <th scope="col">Latest lead</th>
                                            <th scope="col">Category</th>
                                            <th scope="col">Amount</th>
                                            <th scope="col">Status</th>
                                            <th scope="col">Owner</th>
                                            <th scope="col">Created</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($leads->take(5) as $lead)
                                            <tr>
                                                <td><a href="{{ route('leads.show', $lead) }}" class="fw-semibold">{{ $lead->lead_code }}</a></td>
                                                <td>{{ $lead->category?->name ?? '—' }}</td>
                                                <td class="fw-semibold">{{ \App\Support\Format::money($lead->loan_amount) }}</td>
                                                <td><x-status-badge :status="$lead->status" :label="$lead->leadStatus?->name" /></td>
                                                <td>{{ $lead->assignee?->name ?? '—' }}</td>
                                                <td class="text-muted text-nowrap">{{ $lead->created_at?->format('d M Y') }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="6"><x-empty-state icon="bi-funnel" title="No leads yet" message="Create the first lead for this customer." /></td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </x-card>
                    </div>

                    <div class="col-lg-4">
                        <x-card title="Quick actions" icon="bi-lightning-charge">
                            <div class="d-grid gap-2">
                                @canPermission('leads.create')
                                    <a href="{{ route('leads.create', ['customer_id' => $customer->id]) }}" class="lp-quick-action"><i class="bi bi-funnel"></i> Start a new lead</a>
                                @endcanPermission
                                @canPermission('invoices.create')
                                    <a href="{{ route('invoices.create', ['customer_id' => $customer->id]) }}" class="lp-quick-action"><i class="bi bi-receipt"></i> Raise an invoice</a>
                                @endcanPermission
                                @canPermission('payments.create')
                                    <a href="{{ route('payments.create', ['customer_id' => $customer->id]) }}" class="lp-quick-action"><i class="bi bi-wallet2"></i> Record a payment</a>
                                @endcanPermission
                                @canPermission('documents.create')
                                    <button type="button" class="lp-quick-action" data-bs-toggle="modal" data-bs-target="#customer-document-modal">
                                        <i class="bi bi-cloud-arrow-up"></i> Upload document
                                    </button>
                                @endcanPermission
                                <a href="tel:{{ $customer->mobile }}" class="lp-quick-action"><i class="bi bi-telephone"></i> Call {{ $customer->mobile }}</a>
                            </div>
                        </x-card>

                        @if ($customer->notes)
                            <x-card title="Internal notes" icon="bi-sticky" class="mt-4">
                                <p class="mb-0 small text-muted">{{ $customer->notes }}</p>
                            </x-card>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ------------------------------------------------------- personal --}}
            <div class="tab-pane fade" id="customer-tabs-personal" role="tabpanel" aria-labelledby="customer-tabs-personal-tab">
                <x-card title="Personal information" icon="bi-person-vcard">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="lp-kv"><span class="lp-kv__label">Full name</span><span class="lp-kv__value">{{ $customer->name }}</span></div>
                            <div class="lp-kv"><span class="lp-kv__label">Date of birth</span><span class="lp-kv__value">{{ $customer->date_of_birth?->format('d M Y') ?? '—' }}</span></div>
                            <div class="lp-kv"><span class="lp-kv__label">Gender</span><span class="lp-kv__value">{{ \App\Support\Format::titleCase($customer->gender) }}</span></div>
                            <div class="lp-kv"><span class="lp-kv__label">Marital status</span><span class="lp-kv__value">{{ \App\Support\Format::titleCase($customer->marital_status) }}</span></div>
                            <div class="lp-kv"><span class="lp-kv__label">Father / spouse</span><span class="lp-kv__value">{{ $customer->father_or_spouse_name ?? '—' }}</span></div>
                            <div class="lp-kv"><span class="lp-kv__label">Nationality</span><span class="lp-kv__value">{{ $customer->nationality ?? '—' }}</span></div>
                        </div>
                        <div class="col-md-6">
                            <div class="lp-kv"><span class="lp-kv__label">Mobile</span><span class="lp-kv__value">{{ $customer->mobile }}</span></div>
                            <div class="lp-kv"><span class="lp-kv__label">Alternate mobile</span><span class="lp-kv__value">{{ $customer->alternate_mobile ?? '—' }}</span></div>
                            <div class="lp-kv"><span class="lp-kv__label">Email</span><span class="lp-kv__value">{{ $customer->email ?? '—' }}</span></div>
                            <div class="lp-kv"><span class="lp-kv__label">City / State</span><span class="lp-kv__value">{{ collect([$customer->city, $customer->state])->filter()->implode(', ') ?: '—' }}</span></div>
                            <div class="lp-kv"><span class="lp-kv__label">Pincode</span><span class="lp-kv__value">{{ $customer->pincode ?? '—' }}</span></div>
                            <div class="lp-kv"><span class="lp-kv__label">Occupation</span><span class="lp-kv__value">{{ $customer->occupation ?? '—' }}</span></div>
                        </div>
                        <div class="col-12">
                            <div class="lp-kv"><span class="lp-kv__label">Residential address</span><span class="lp-kv__value">{{ $customer->address ?? '—' }}</span></div>
                        </div>
                    </div>

                    @if ($customer->addresses->isNotEmpty())
                        <div class="lp-divider"></div>
                        <div class="fw-semibold mb-3">Addresses on record</div>
                        <div class="table-responsive">
                            <table class="lp-table">
                                <thead>
                                    <tr>
                                        <th scope="col">Type</th>
                                        <th scope="col">Address</th>
                                        <th scope="col">City</th>
                                        <th scope="col">Pincode</th>
                                        <th scope="col">Primary</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($customer->addresses as $address)
                                        <tr>
                                            <td class="fw-semibold">{{ \App\Support\Format::titleCase($address->address_type) }}</td>
                                            <td>{{ $address->address_line }}</td>
                                            <td>{{ $address->city ?? '—' }}</td>
                                            <td>{{ $address->pincode ?? '—' }}</td>
                                            <td>{!! $address->is_primary ? '<span class="lp-badge bg-success-subtle text-success bg-opacity-10">Primary</span>' : '—' !!}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </x-card>
            </div>

            {{-- --------------------------------------------------- professional --}}
            <div class="tab-pane fade" id="customer-tabs-professional" role="tabpanel" aria-labelledby="customer-tabs-professional-tab">
                <x-card title="Professional details" icon="bi-briefcase">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="lp-kv"><span class="lp-kv__label">Employment type</span><span class="lp-kv__value">{{ $customer->employmentType?->name ?? '—' }}</span></div>
                            <div class="lp-kv"><span class="lp-kv__label">Employer / business</span><span class="lp-kv__value">{{ $customer->company_name ?? '—' }}</span></div>
                            <div class="lp-kv"><span class="lp-kv__label">Designation</span><span class="lp-kv__value">{{ $customer->designation ?? '—' }}</span></div>
                            <div class="lp-kv"><span class="lp-kv__label">Experience</span><span class="lp-kv__value">{{ $customer->work_experience_years ? $customer->work_experience_years.' years' : '—' }}</span></div>
                        </div>
                        <div class="col-md-6">
                            <div class="lp-kv"><span class="lp-kv__label">Monthly income</span><span class="lp-kv__value">{{ \App\Support\Format::money($customer->monthly_income) }}</span></div>
                            <div class="lp-kv"><span class="lp-kv__label">Annual income</span><span class="lp-kv__value">{{ \App\Support\Format::money($customer->annual_income) }}</span></div>
                            <div class="lp-kv"><span class="lp-kv__label">Office address</span><span class="lp-kv__value">{{ $customer->office_address ?? '—' }}</span></div>
                            <div class="lp-kv"><span class="lp-kv__label">Occupation</span><span class="lp-kv__value">{{ $customer->occupation ?? '—' }}</span></div>
                        </div>
                    </div>

                    @if ($customer->professionalDetails->isNotEmpty())
                        <div class="lp-divider"></div>
                        <div class="fw-semibold mb-3">Employment history</div>
                        <div class="table-responsive">
                            <table class="lp-table">
                                <thead>
                                    <tr>
                                        <th scope="col">Employer</th>
                                        <th scope="col">Designation</th>
                                        <th scope="col">Industry</th>
                                        <th scope="col">Income</th>
                                        <th scope="col">Current</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($customer->professionalDetails as $detail)
                                        <tr>
                                            <td class="fw-semibold">{{ $detail->company_name ?? '—' }}</td>
                                            <td>{{ $detail->designation ?? '—' }}</td>
                                            <td>{{ $detail->industry ?? '—' }}</td>
                                            <td>{{ \App\Support\Format::money($detail->monthly_income) }}</td>
                                            <td>{!! $detail->is_current ? '<span class="lp-badge bg-success-subtle text-success bg-opacity-10">Yes</span>' : 'No' !!}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </x-card>
            </div>

            {{-- ------------------------------------------------------------ kyc --}}
            <div class="tab-pane fade" id="customer-tabs-kyc" role="tabpanel" aria-labelledby="customer-tabs-kyc-tab">
                <div class="row g-4">
                    <div class="col-lg-5">
                        <x-card title="KYC status" icon="bi-patch-check">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <span class="lp-stat__icon lp-tone-{{ $customer->kyc_status === 'verified' ? 'success' : ($customer->kyc_status === 'rejected' ? 'danger' : 'warning') }}">
                                    <i class="bi bi-{{ $customer->kyc_status === 'verified' ? 'patch-check-fill' : 'shield-exclamation' }}"></i>
                                </span>
                                <div>
                                    <div class="fw-semibold">{{ \App\Support\Format::titleCase($customer->kyc_status) }}</div>
                                    <div class="text-muted small">PAN, Aadhaar and document verification state</div>
                                </div>
                            </div>
                            <div class="lp-kv"><span class="lp-kv__label">PAN number</span><span class="lp-kv__value">{{ $customer->pan_number ?? '—' }}</span></div>
                            <div class="lp-kv"><span class="lp-kv__label">Aadhaar number</span><span class="lp-kv__value">{{ $customer->aadhaar_number ? \App\Support\Format::mask($customer->aadhaar_number) : '—' }}</span></div>
                            <div class="lp-kv"><span class="lp-kv__label">Documents uploaded</span><span class="lp-kv__value">{{ $documents->count() }}</span></div>
                            <div class="lp-kv"><span class="lp-kv__label">Verified documents</span><span class="lp-kv__value">{{ $documents->where('status', 'verified')->count() }}</span></div>
                        </x-card>
                    </div>

                    <div class="col-lg-7">
                        <x-card title="Document checklist" icon="bi-list-check">
                            <div class="table-responsive">
                                <table class="lp-table">
                                    <thead>
                                        <tr>
                                            <th scope="col">Document</th>
                                            <th scope="col">Status</th>
                                            <th scope="col">Uploaded</th>
                                            <th scope="col" class="text-end">File</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($documents as $document)
                                            <tr>
                                                <td class="fw-semibold">{{ $document->documentType?->name ?? 'Document' }}</td>
                                                <td><x-status-badge :status="$document->status" /></td>
                                                <td class="text-muted text-nowrap">{{ $document->created_at?->format('d M Y') }}</td>
                                                <td class="text-end">
                                                    <a href="{{ route('documents.preview', $document) }}" target="_blank" rel="noopener" class="btn btn-sm btn-light"><i class="bi bi-eye"></i></a>
                                                    <a href="{{ route('documents.download', $document) }}" class="btn btn-sm btn-light"><i class="bi bi-download"></i></a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4"><x-empty-state icon="bi-folder2-open" title="No documents" message="Upload PAN, Aadhaar and address proof to complete KYC." /></td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </x-card>
                    </div>
                </div>
            </div>

            {{-- ---------------------------------------------------------- leads --}}
            <div class="tab-pane fade" id="customer-tabs-leads" role="tabpanel" aria-labelledby="customer-tabs-leads-tab">
                <x-card title="Leads" icon="bi-funnel">
                    <div class="table-responsive">
                        <table class="lp-table">
                            <thead>
                                <tr>
                                    <th scope="col">Lead ID</th>
                                    <th scope="col">Category</th>
                                    <th scope="col">Purpose</th>
                                    <th scope="col">Amount</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Owner</th>
                                    <th scope="col">Created</th>
                                    <th scope="col" class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($leads as $lead)
                                    <tr>
                                        <td><a href="{{ route('leads.show', $lead) }}" class="fw-semibold">{{ $lead->lead_code }}</a></td>
                                        <td>{{ $lead->category?->name ?? '—' }}</td>
                                        <td>{{ $lead->subcategory?->name ?? '—' }}</td>
                                        <td class="fw-semibold">{{ \App\Support\Format::money($lead->loan_amount) }}</td>
                                        <td><x-status-badge :status="$lead->status" :label="$lead->leadStatus?->name" /></td>
                                        <td>{{ $lead->assignee?->name ?? '—' }}</td>
                                        <td class="text-muted text-nowrap">{{ $lead->created_at?->format('d M Y') }}</td>
                                        <td class="text-end"><a href="{{ route('leads.show', $lead) }}" class="btn btn-sm btn-light"><i class="bi bi-eye"></i></a></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8"><x-empty-state icon="bi-funnel" title="No leads for this customer" message="Start a lead to begin the lending journey." /></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-card>
            </div>

            {{-- --------------------------------------------------- applications --}}
            <div class="tab-pane fade" id="customer-tabs-applications" role="tabpanel" aria-labelledby="customer-tabs-applications-tab">
                <x-card title="Applications" icon="bi-clipboard-data">
                    <div class="table-responsive">
                        <table class="lp-table">
                            <thead>
                                <tr>
                                    <th scope="col">Application ID</th>
                                    <th scope="col">Lender</th>
                                    <th scope="col">Category</th>
                                    <th scope="col">Amount</th>
                                    <th scope="col">Sanctioned</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Created</th>
                                    <th scope="col" class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($applications as $application)
                                    <tr>
                                        <td><a href="{{ route('applications.show', $application) }}" class="fw-semibold">{{ $application->application_code }}</a></td>
                                        <td>{{ $application->lender?->name ?? '—' }}</td>
                                        <td>{{ $application->category?->name ?? '—' }}</td>
                                        <td class="fw-semibold">{{ \App\Support\Format::money($application->loan_amount) }}</td>
                                        <td>{{ \App\Support\Format::money($application->sanctioned_amount) }}</td>
                                        <td><x-status-badge :status="$application->status" :label="$application->applicationStatus?->name" /></td>
                                        <td class="text-muted text-nowrap">{{ $application->created_at?->format('d M Y') }}</td>
                                        <td class="text-end"><a href="{{ route('applications.show', $application) }}" class="btn btn-sm btn-light"><i class="bi bi-eye"></i></a></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8"><x-empty-state icon="bi-clipboard-data" title="No applications" message="Applications created from leads appear here." /></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-card>
            </div>

            {{-- ---------------------------------------------------------- loans --}}
            <div class="tab-pane fade" id="customer-tabs-loans" role="tabpanel" aria-labelledby="customer-tabs-loans-tab">
                <x-card title="Loan book" icon="bi-cash-coin" description="Disbursed and running loans for this customer.">
                    <div class="table-responsive">
                        <table class="lp-table">
                            <thead>
                                <tr>
                                    <th scope="col">Loan</th>
                                    <th scope="col">Application</th>
                                    <th scope="col">Disbursed</th>
                                    <th scope="col">ROI</th>
                                    <th scope="col">Tenure</th>
                                    <th scope="col">EMI</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $loanRows = $applications->whereIn('status', ['disbursed', 'closed', 'approved']); @endphp
                                @forelse ($loanRows as $loan)
                                    <tr>
                                        <td class="fw-semibold">{{ $loan->application_code }}</td>
                                        <td>{{ $loan->lender?->name ?? '—' }}</td>
                                        <td class="fw-semibold">{{ \App\Support\Format::money($loan->disbursed_amount) }}</td>
                                        <td>{{ $loan->roi !== null ? $loan->roi.'%' : '—' }}</td>
                                        <td>{{ \App\Support\Format::tenure($loan->tenure_months) }}</td>
                                        <td>{{ \App\Support\Format::money($loan->emi) }}</td>
                                        <td><x-status-badge :status="$loan->status" /></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7"><x-empty-state icon="bi-cash-coin" title="No active loans" message="Approved and disbursed loans will be listed here." /></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-card>
            </div>

            {{-- ------------------------------------------------------ insurance --}}
            <div class="tab-pane fade" id="customer-tabs-insurance" role="tabpanel" aria-labelledby="customer-tabs-insurance-tab">
                <x-card title="Insurance policies" icon="bi-shield-check">
                    <div class="table-responsive">
                        <table class="lp-table">
                            <thead>
                                <tr>
                                    <th scope="col">Policy</th>
                                    <th scope="col">Insurer</th>
                                    <th scope="col">Plan</th>
                                    <th scope="col">Sum insured</th>
                                    <th scope="col">Premium</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($insurance as $policy)
                                    <tr>
                                        <td><a href="{{ route('insurance.show', $policy) }}" class="fw-semibold">{{ $policy->application_code }}</a></td>
                                        <td>{{ $policy->lender?->name ?? '—' }}</td>
                                        <td>{{ $policy->plan?->name ?? $policy->insuranceType?->name ?? '—' }}</td>
                                        <td class="fw-semibold">{{ \App\Support\Format::money($policy->sum_assured) }}</td>
                                        <td>{{ \App\Support\Format::money($policy->premium_amount) }}</td>
                                        <td><x-status-badge :status="$policy->status" /></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6"><x-empty-state icon="bi-shield-check" title="No insurance policies" message="Insurance applications raised for this customer appear here." /></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-card>
            </div>

            {{-- ------------------------------------------------------- payments --}}
            <div class="tab-pane fade" id="customer-tabs-payments" role="tabpanel" aria-labelledby="customer-tabs-payments-tab">
                <div class="row g-4">
                    <div class="col-lg-7">
                        <x-card title="Payment history" icon="bi-wallet2">
                            <div class="table-responsive">
                                <table class="lp-table">
                                    <thead>
                                        <tr>
                                            <th scope="col">Reference</th>
                                            <th scope="col">Date</th>
                                            <th scope="col">Mode</th>
                                            <th scope="col">Amount</th>
                                            <th scope="col">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($payments as $payment)
                                            <tr>
                                                <td><a href="{{ route('payments.show', $payment) }}" class="fw-semibold">{{ $payment->payment_code }}</a></td>
                                                <td class="text-muted text-nowrap">{{ $payment->payment_date?->format('d M Y') }}</td>
                                                <td>{{ $payment->paymentMethod?->name ?? '—' }}</td>
                                                <td class="fw-semibold">{{ \App\Support\Format::money($payment->amount) }}</td>
                                                <td><x-status-badge :status="$payment->status" /></td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="5"><x-empty-state icon="bi-wallet2" title="No payments recorded" message="Collections against invoices show up here." /></td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </x-card>
                    </div>

                    <div class="col-lg-5">
                        <x-card title="Invoices" icon="bi-receipt">
                            <div class="table-responsive">
                                <table class="lp-table">
                                    <thead>
                                        <tr>
                                            <th scope="col">Invoice</th>
                                            <th scope="col">Amount</th>
                                            <th scope="col">Balance</th>
                                            <th scope="col">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($invoices as $invoice)
                                            <tr>
                                                <td><a href="{{ route('invoices.show', $invoice) }}" class="fw-semibold">{{ $invoice->invoice_number }}</a></td>
                                                <td>{{ \App\Support\Format::money($invoice->total) }}</td>
                                                <td>{{ \App\Support\Format::money($invoice->balance_amount) }}</td>
                                                <td><x-status-badge :status="$invoice->status" /></td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4"><x-empty-state icon="bi-receipt" title="No invoices" message="Raise an invoice for fees and premiums." /></td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </x-card>
                    </div>
                </div>
            </div>

            {{-- ------------------------------------------------------ documents --}}
            <div class="tab-pane fade" id="customer-tabs-documents" role="tabpanel" aria-labelledby="customer-tabs-documents-tab">
                <x-card title="Customer documents" icon="bi-folder-check" description="Stored privately — downloads are authorised per user.">
                    <x-slot:actions>
                        @can('create', App\Models\Document::class)
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#customer-document-modal">
                                <i class="bi bi-cloud-arrow-up"></i> Upload document
                            </button>
                        @endcan
                    </x-slot:actions>

                    <div class="table-responsive">
                        <table class="lp-table">
                            <thead>
                                <tr>
                                    <th scope="col">Document</th>
                                    <th scope="col">Number</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Verified by</th>
                                    <th scope="col">Uploaded</th>
                                    <th scope="col" class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($documents as $document)
                                    <tr>
                                        <td class="fw-semibold">{{ $document->documentType?->name ?? 'Document' }}</td>
                                        <td>{{ $document->issued_number ?? '—' }}</td>
                                        <td><x-status-badge :status="$document->status" /></td>
                                        <td>{{ $document->verifier?->name ?? '—' }}</td>
                                        <td class="text-muted text-nowrap">{{ $document->created_at?->format('d M Y') }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('documents.preview', $document) }}" target="_blank" rel="noopener" class="btn btn-sm btn-light"><i class="bi bi-eye"></i></a>
                                            <a href="{{ route('documents.download', $document) }}" class="btn btn-sm btn-light"><i class="bi bi-download"></i></a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6"><x-empty-state icon="bi-folder2-open" title="No documents uploaded" message="Add identity, address and income proofs." /></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-card>
            </div>

            {{-- ------------------------------------------------------- activity --}}
            <div class="tab-pane fade" id="customer-tabs-activity" role="tabpanel" aria-labelledby="customer-tabs-activity-tab">
                <x-card title="Activity &amp; remarks" icon="bi-activity">
                    <div id="customer-activity-list">
                        @forelse ($activities as $activity)
                            @include('leads.partials.remark', ['remark' => $activity])
                        @empty
                            <x-empty-state icon="bi-activity" title="No activity logged" message="Calls, meetings and notes recorded by the team appear here." />
                        @endforelse
                    </div>
                </x-card>
            </div>
        </div>
    </x-tabs>

    @can('create', App\Models\Document::class)
        <x-modal id="customer-document-modal" title="Upload customer document" :action="route('documents.store')" submit-label="Upload" size="lg">
            <input type="hidden" name="documentable_type" value="customer">
            <input type="hidden" name="documentable_id" value="{{ $customer->id }}">

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="document_type_id">Document type<span class="req">*</span></label>
                    <select class="form-select" id="document_type_id" name="document_type_id" required>
                        @foreach (App\Models\DocumentType::query()->active()->ordered()->get() as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3"><x-input name="issued_number" label="Document number" /></div>
                <div class="col-md-3"><x-input name="expires_at" label="Expiry date" type="date" /></div>
                <div class="col-12"><x-file-uploader name="file" label="File" required /></div>
            </div>
        </x-modal>
    @endcan
@endsection
