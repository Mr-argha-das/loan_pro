@extends('layouts.app')

@section('title', 'Lead '.$lead->lead_code)
@section('page-header', true)
@section('page-title', $lead->customer?->name ?? 'Lead '.$lead->lead_code)
@section('page-subtitle', $lead->lead_code.' · '.($lead->category?->name ?? 'Uncategorised').' · Created '.$lead->created_at?->format('d M Y'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('leads.index') }}">Leads</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $lead->lead_code }}</li>
@endsection

@section('page-actions')
    <div class="d-flex flex-wrap gap-2">
        @can('update', $lead)
            <a href="{{ route('leads.wizard', ['lead' => $lead, 'step' => min(10, max(1, $lead->current_step))]) }}" class="btn btn-light btn-sm">
                <i class="bi bi-pencil"></i> Edit
            </a>
        @endcan
        @can('assign', $lead)
            <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#assign-lead-modal">
                <i class="bi bi-person-plus"></i> Assign
            </button>
        @endcan
        @can('update', $lead)
            <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#status-lead-modal">
                <i class="bi bi-arrow-repeat"></i> Change Status
            </button>
        @endcan
        @can('convert', $lead)
            <form method="POST" action="{{ route('leads.convert', $lead) }}" class="d-inline" data-confirm="Create a loan application from this lead?">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-arrow-right-circle"></i> Create Application</button>
            </form>
        @endcan
        @can('delete', $lead)
            <form method="POST" action="{{ route('leads.destroy', $lead) }}" class="d-inline" data-confirm="Delete lead {{ $lead->lead_code }}? This cannot be undone.">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
            </form>
        @endcan
    </div>
@endsection

@section('content')
    {{-- ----------------------------------------------------------- headline --}}
    <div class="lp-card mb-4">
        <div class="lp-card__body">
            <div class="row g-4 align-items-center">
                <div class="col-lg-4">
                    <div class="d-flex align-items-center gap-3">
                        <span class="lp-avatar lp-avatar--lg">{{ $lead->customer?->initials() }}</span>
                        <div>
                            <div class="fw-semibold">{{ $lead->customer?->name }}</div>
                            <div class="text-muted small">{{ $lead->customer?->mobile }} · {{ $lead->customer?->email ?? 'No email' }}</div>
                            <div class="d-flex flex-wrap gap-1 mt-2">
                                <x-status-badge :status="$lead->status" :label="$lead->leadStatus?->name" />
                                @if ($lead->is_otp_verified)
                                    <span class="lp-badge bg-success-subtle text-success bg-opacity-10"><i class="bi bi-patch-check-fill"></i> Verified</span>
                                @else
                                    <span class="lp-badge bg-warning-subtle text-warning bg-opacity-10"><i class="bi bi-shield-exclamation"></i> OTP pending</span>
                                @endif
                                @if ($lead->is_converted)
                                    <span class="lp-badge bg-primary-subtle text-primary bg-opacity-10"><i class="bi bi-check2-circle"></i> Converted</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="row g-3">
                        <div class="col-6 col-md-3">
                            <x-stat-card label="Loan amount" :value="\App\Support\Format::money($lead->loan_amount)" icon="bi-cash-coin" tone="primary" />
                        </div>
                        <div class="col-6 col-md-3">
                            <x-stat-card label="Tenure" :value="\App\Support\Format::tenure($lead->tenure_months)" icon="bi-calendar3" tone="blue" />
                        </div>
                        <div class="col-6 col-md-3">
                            <x-stat-card label="Lenders shortlisted" :value="$lead->lenders->count()" icon="bi-bank" tone="green" />
                        </div>
                        <div class="col-6 col-md-3">
                            <x-stat-card label="Progress" :value="$lead->progressPercent().'%'" icon="bi-speedometer2" tone="orange" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="lp-divider"></div>

            <div class="row g-2 small">
                <div class="col-6 col-md-3"><span class="text-muted">Product:</span> <span class="fw-semibold">{{ $lead->product?->name ?? '—' }}</span></div>
                <div class="col-6 col-md-3"><span class="text-muted">Purpose:</span> <span class="fw-semibold">{{ $lead->subcategory?->name ?? '—' }}</span></div>
                <div class="col-6 col-md-3"><span class="text-muted">Source:</span> <span class="fw-semibold">{{ $lead->source?->name ?? '—' }}</span></div>
                <div class="col-6 col-md-3"><span class="text-muted">Owner:</span> <span class="fw-semibold">{{ $lead->assignee?->name ?? 'Unassigned' }}</span></div>
                <div class="col-6 col-md-3"><span class="text-muted">Credit score:</span> <span class="fw-semibold">{{ $lead->credit_score ?? '—' }}</span></div>
                <div class="col-6 col-md-3"><span class="text-muted">Existing EMI:</span> <span class="fw-semibold">{{ \App\Support\Format::money($lead->existing_emi) }}</span></div>
                <div class="col-6 col-md-3"><span class="text-muted">Last activity:</span> <span class="fw-semibold">{{ $lead->last_activity_at?->diffForHumans() ?? '—' }}</span></div>
                <div class="col-6 col-md-3"><span class="text-muted">Created by:</span> <span class="fw-semibold">{{ $lead->creator?->name ?? '—' }}</span></div>
            </div>
        </div>
    </div>

    <x-tabs id="lead-tabs" :tabs="[
        'overview' => ['label' => 'Overview', 'icon' => 'bi-grid'],
        'journey' => ['label' => 'Lead Journey', 'icon' => 'bi-signpost-split'],
        'lenders' => ['label' => 'Lenders', 'icon' => 'bi-bank', 'count' => $lead->lenders->count()],
        'documents' => ['label' => 'Documents', 'icon' => 'bi-folder-check', 'count' => $lead->documents->count()],
        'applications' => ['label' => 'Applications', 'icon' => 'bi-clipboard-data', 'count' => $lead->applications->count()],
        'remarks' => ['label' => 'Remarks', 'icon' => 'bi-chat-left-text', 'count' => $lead->remarks->count()],
    ]">

        <div class="tab-content lp-tab-content">
            {{-- ------------------------------------------------------- overview --}}
            <div class="tab-pane fade show active" id="lead-tabs-overview" role="tabpanel" aria-labelledby="lead-tabs-overview-tab">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <x-card title="Customer & KYC" icon="bi-person-vcard">
                            <div class="lp-kv"><span class="lp-kv__label">Full name</span><span class="lp-kv__value">{{ $lead->customer?->name ?? '—' }}</span></div>
                            <div class="lp-kv"><span class="lp-kv__label">Date of birth</span><span class="lp-kv__value">{{ $lead->customer?->date_of_birth?->format('d M Y') ?? '—' }}</span></div>
                            <div class="lp-kv"><span class="lp-kv__label">PAN</span><span class="lp-kv__value">{{ $lead->customer?->pan_number ?? '—' }}</span></div>
                            <div class="lp-kv"><span class="lp-kv__label">Aadhaar</span><span class="lp-kv__value">{{ $lead->customer?->aadhaar_number ? \App\Support\Format::mask($lead->customer->aadhaar_number) : '—' }}</span></div>
                            <div class="lp-kv"><span class="lp-kv__label">Address</span><span class="lp-kv__value">{{ collect([$lead->customer?->address, $lead->customer?->city, $lead->customer?->pincode])->filter()->implode(', ') ?: '—' }}</span></div>
                            <div class="lp-kv"><span class="lp-kv__label">KYC status</span><span class="lp-kv__value"><x-status-badge :status="$lead->customer?->kyc_status ?? 'pending'" /></span></div>
                        </x-card>
                    </div>

                    <div class="col-lg-6">
                        <x-card title="Professional & Income" icon="bi-briefcase">
                            <div class="lp-kv"><span class="lp-kv__label">Employment</span><span class="lp-kv__value">{{ $lead->customer?->employmentType?->name ?? $lead->employmentType?->name ?? '—' }}</span></div>
                            <div class="lp-kv"><span class="lp-kv__label">Employer</span><span class="lp-kv__value">{{ $lead->customer?->company_name ?? '—' }}</span></div>
                            <div class="lp-kv"><span class="lp-kv__label">Designation</span><span class="lp-kv__value">{{ $lead->customer?->designation ?? '—' }}</span></div>
                            <div class="lp-kv"><span class="lp-kv__label">Monthly income</span><span class="lp-kv__value">{{ \App\Support\Format::money($lead->customer?->monthly_income) }}</span></div>
                            <div class="lp-kv"><span class="lp-kv__label">Experience</span><span class="lp-kv__value">{{ $lead->customer?->work_experience_years ? $lead->customer->work_experience_years.' years' : '—' }}</span></div>
                            <div class="lp-kv"><span class="lp-kv__label">Office address</span><span class="lp-kv__value">{{ $lead->customer?->office_address ?? '—' }}</span></div>
                        </x-card>
                    </div>

                    @if ($lead->remarks->isNotEmpty())
                        <div class="col-12">
                            <x-card title="Latest remarks" icon="bi-chat-left-text">
                                @foreach ($lead->remarks->take(3) as $remark)
                                    @include('leads.partials.remark', ['remark' => $remark])
                                @endforeach
                            </x-card>
                        </div>
                    @endif
                </div>
            </div>

            {{-- -------------------------------------------------------- journey --}}
            <div class="tab-pane fade" id="lead-tabs-journey" role="tabpanel" aria-labelledby="lead-tabs-journey-tab">
                <div class="row g-4">
                    <div class="col-lg-4">
                        <x-card title="Lead journey" icon="bi-signpost-split">
                            <x-lead-journey :lead="$lead" />
                        </x-card>
                    </div>

                    <div class="col-lg-8">
                        <x-card title="Status history" icon="bi-clock-history" description="Every status change is recorded forever — history is never overwritten.">
                            <div class="table-responsive">
                                <table class="lp-table">
                                    <thead>
                                        <tr>
                                            <th scope="col">Stage</th>
                                            <th scope="col">Status</th>
                                            <th scope="col">Changed by</th>
                                            <th scope="col">Note</th>
                                            <th scope="col">When</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($lead->statusHistories->sortByDesc('created_at') as $history)
                                            <tr>
                                                <td class="fw-semibold">{{ $history->stage ?? '—' }}</td>
                                                <td><x-status-badge :status="$history->status?->slug ?? 'pending'" :label="$history->status?->name" /></td>
                                                <td>{{ $history->changedBy?->name ?? 'System' }}</td>
                                                <td class="text-muted">{{ $history->note ?? '—' }}</td>
                                                <td class="text-muted text-nowrap">{{ $history->created_at?->format('d M Y, h:i A') }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="5"><x-empty-state icon="bi-clock-history" title="No history yet" message="Status changes will appear here." /></td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </x-card>

                        <x-card title="Assignment history" icon="bi-people" class="mt-4">
                            <div class="table-responsive">
                                <table class="lp-table">
                                    <thead>
                                        <tr>
                                            <th scope="col">Assigned to</th>
                                            <th scope="col">By</th>
                                            <th scope="col">Note</th>
                                            <th scope="col">When</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($lead->assignments->sortByDesc('created_at') as $assignment)
                                            <tr>
                                                <td class="fw-semibold">{{ $assignment->assignee?->name ?? '—' }}</td>
                                                <td>{{ $assignment->assigner?->name ?? 'System' }}</td>
                                                <td class="text-muted">{{ $assignment->note ?? '—' }}</td>
                                                <td class="text-muted text-nowrap">{{ $assignment->created_at?->format('d M Y, h:i A') }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4"><x-empty-state icon="bi-people" title="No assignments yet" message="Assign this lead to a relationship manager." /></td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </x-card>
                    </div>
                </div>
            </div>

            {{-- -------------------------------------------------------- lenders --}}
            <div class="tab-pane fade" id="lead-tabs-lenders" role="tabpanel" aria-labelledby="lead-tabs-lenders-tab">
                <x-card title="Shortlisted lenders" icon="bi-bank" description="Per-lender configuration captured during the wizard.">
                    <div class="table-responsive">
                        <table class="lp-table">
                            <thead>
                                <tr>
                                    <th scope="col">Lender</th>
                                    <th scope="col">Product</th>
                                    <th scope="col">Amount</th>
                                    <th scope="col">ROI</th>
                                    <th scope="col">APR</th>
                                    <th scope="col">EMI</th>
                                    <th scope="col">Tenure</th>
                                    <th scope="col">Processing fee</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($lead->lenders as $lender)
                                    <tr>
                                        <td class="fw-semibold">
                                            {{ $lender->lender?->name }}
                                            @if ($lender->is_primary)<span class="lp-badge bg-success-subtle text-success bg-opacity-10 ms-1">Primary</span>@endif
                                        </td>
                                        <td>{{ $lender->lenderProduct?->product_name ?? '—' }}</td>
                                        <td>{{ \App\Support\Format::money($lender->loan_amount) }}</td>
                                        <td>{{ $lender->roi !== null ? $lender->roi.'%' : '—' }}</td>
                                        <td>{{ $lender->apr !== null ? $lender->apr.'%' : '—' }}</td>
                                        <td>{{ \App\Support\Format::money($lender->emi) }}</td>
                                        <td>{{ $lender->tenure_months }} months</td>
                                        <td>{{ \App\Support\Format::money($lender->processing_fee) }}</td>
                                        <td><x-status-badge :status="$lender->status ?? 'selected'" /></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9">
                                            <x-empty-state icon="bi-bank" title="No lenders shortlisted" message="Use step 8 of the wizard to compare and select lenders.">
                                                <x-slot:action>
                                                    @can('update', $lead)
                                                        <a href="{{ route('leads.wizard', ['lead' => $lead, 'step' => 8]) }}" class="btn btn-primary btn-sm">Select lenders</a>
                                                    @endcan
                                                </x-slot:action>
                                            </x-empty-state>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-card>
            </div>

            {{-- ------------------------------------------------------ documents --}}
            <div class="tab-pane fade" id="lead-tabs-documents" role="tabpanel" aria-labelledby="lead-tabs-documents-tab">
                <x-card title="Documents" icon="bi-folder-check" description="Files are stored privately and never exposed publicly.">
                    <x-slot:actions>
                        @can('create', App\Models\Document::class)
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#upload-document-modal">
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
                                    <th scope="col">Uploaded by</th>
                                    <th scope="col">Uploaded</th>
                                    <th scope="col" class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($lead->documents as $document)
                                    <tr>
                                        <td class="fw-semibold">
                                            {{ $document->documentType?->name ?? 'Document' }}
                                            @if ($document->is_required)<span class="text-danger">*</span>@endif
                                        </td>
                                        <td>{{ $document->issued_number ?? '—' }}</td>
                                        <td><x-status-badge :status="$document->status" /></td>
                                        <td>{{ $document->uploader?->name ?? '—' }}</td>
                                        <td class="text-muted text-nowrap">{{ $document->created_at?->format('d M Y') }}</td>
                                        <td class="text-end">
                                            <div class="lp-row-actions">
                                                <a href="{{ route('documents.preview', $document) }}" target="_blank" rel="noopener" class="btn btn-sm btn-light" title="Preview"><i class="bi bi-eye"></i></a>
                                                <a href="{{ route('documents.download', $document) }}" class="btn btn-sm btn-light" title="Download"><i class="bi bi-download"></i></a>
                                                @can('verify', $document)
                                                    <form method="POST" action="{{ route('documents.verify', $document) }}" class="d-inline">
                                                        @csrf
                                                        <button class="btn btn-sm btn-light text-success" title="Verify"><i class="bi bi-check2-circle"></i></button>
                                                    </form>
                                                    <form method="POST" action="{{ route('documents.reject', $document) }}" class="d-inline" data-confirm="Reject this document?">
                                                        @csrf
                                                        <button class="btn btn-sm btn-light text-danger" title="Reject"><i class="bi bi-x-circle"></i></button>
                                                    </form>
                                                @endcan
                                                @can('delete', $document)
                                                    <form method="POST" action="{{ route('documents.destroy', $document) }}" class="d-inline" data-confirm="Delete this document?">
                                                        @csrf @method('DELETE')
                                                        <button class="btn btn-sm btn-light text-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                                    </form>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6"><x-empty-state icon="bi-folder2-open" title="No documents uploaded" message="Upload identity, address and income proofs to strengthen the file." /></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-card>
            </div>

            {{-- --------------------------------------------------- applications --}}
            <div class="tab-pane fade" id="lead-tabs-applications" role="tabpanel" aria-labelledby="lead-tabs-applications-tab">
                <x-card title="Linked applications" icon="bi-clipboard-data">
                    <div class="table-responsive">
                        <table class="lp-table">
                            <thead>
                                <tr>
                                    <th scope="col">Application ID</th>
                                    <th scope="col">Lender</th>
                                    <th scope="col">Category</th>
                                    <th scope="col">Amount</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Created</th>
                                    <th scope="col" class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($lead->applications as $application)
                                    <tr>
                                        <td><a href="{{ route('applications.show', $application) }}" class="fw-semibold">{{ $application->application_code }}</a></td>
                                        <td>{{ $application->lender?->name ?? '—' }}</td>
                                        <td>{{ $application->category?->name ?? '—' }}</td>
                                        <td class="fw-semibold">{{ \App\Support\Format::money($application->loan_amount) }}</td>
                                        <td><x-status-badge :status="$application->status" :label="$application->applicationStatus?->name" /></td>
                                        <td class="text-muted text-nowrap">{{ $application->created_at?->format('d M Y') }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('applications.show', $application) }}" class="btn btn-sm btn-light"><i class="bi bi-eye"></i></a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7">
                                            <x-empty-state icon="bi-clipboard-data" title="No applications yet" message="Convert this lead once the customer confirms a lender.">
                                                <x-slot:action>
                                                    @can('convert', $lead)
                                                        <form method="POST" action="{{ route('leads.convert', $lead) }}">
                                                            @csrf
                                                            <button class="btn btn-primary btn-sm">Create application</button>
                                                        </form>
                                                    @endcan
                                                </x-slot:action>
                                            </x-empty-state>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-card>
            </div>

            {{-- ------------------------------------------------------- remarks --}}
            <div class="tab-pane fade" id="lead-tabs-remarks" role="tabpanel" aria-labelledby="lead-tabs-remarks-tab">
                <x-card title="Remarks & activity" icon="bi-chat-left-text">
                    <form method="POST" action="{{ route('leads.remarks.store', $lead) }}" id="lead-remark-form" data-ajax-refresh="#lead-remark-list">
                        @csrf
                        <div class="row g-2">
                            <div class="col-md-9">
                                <textarea name="body" class="form-control" rows="2" required placeholder="Log a call, meeting or note for this lead…"></textarea>
                            </div>
                            <div class="col-md-3">
                                <select name="type" class="form-select mb-2">
                                    <option value="comment">Comment</option>
                                    <option value="call">Call</option>
                                    <option value="meeting">Meeting</option>
                                    <option value="note">Note</option>
                                </select>
                                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-send"></i> Add remark</button>
                            </div>
                        </div>
                    </form>

                    <div class="lp-divider"></div>

                    <div id="lead-remark-list">
                        @forelse ($lead->remarks->sortByDesc('created_at') as $remark)
                            @include('leads.partials.remark', ['remark' => $remark])
                        @empty
                            <x-empty-state icon="bi-chat-left-text" title="No remarks yet" message="Conversations and follow-ups logged here build the lead's audit trail." />
                        @endforelse
                    </div>
                </x-card>
            </div>
        </div>
    </x-tabs>

    {{-- ------------------------------------------------------------- modals --}}
    @can('update', $lead)
        <x-modal id="status-lead-modal" title="Change lead status" :action="route('leads.status', $lead)" submit-label="Update status">
            <div class="mb-3">
                <label class="form-label" for="lead_status_id">New status<span class="req">*</span></label>
                <select class="form-select" id="lead_status_id" name="lead_status_id" required>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->id }}" @selected($lead->lead_status_id === $status->id)>{{ $status->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="status-note">Note</label>
                <textarea class="form-control" id="status-note" name="note" rows="2" placeholder="Reason for this change (recorded in history)"></textarea>
            </div>
        </x-modal>
    @endcan

    @can('assign', $lead)
        <x-modal id="assign-lead-modal" title="Assign lead" :action="route('leads.assign', $lead)" submit-label="Assign">
            <div class="mb-3">
                <label class="form-label" for="assigned_to">Assign to<span class="req">*</span></label>
                <select class="form-select" id="assigned_to" name="assigned_to" required>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}" @selected($lead->assigned_to === $employee->id)>
                            {{ $employee->name }}{{ $employee->designation ? ' — '.$employee->designation : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="assign-note">Note</label>
                <textarea class="form-control" id="assign-note" name="note" rows="2"></textarea>
            </div>
        </x-modal>
    @endcan

    @can('create', App\Models\Document::class)
        <x-modal id="upload-document-modal" title="Upload document" :action="route('documents.store')" submit-label="Upload" size="lg">
            <input type="hidden" name="documentable_type" value="lead">
            <input type="hidden" name="documentable_id" value="{{ $lead->id }}">

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="document_type_id">Document type<span class="req">*</span></label>
                    <select class="form-select" id="document_type_id" name="document_type_id" required>
                        @foreach ($documentTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3"><x-input name="issued_number" label="Document number" /></div>
                <div class="col-md-3"><x-input name="expires_at" label="Expiry date" type="date" /></div>
                <div class="col-12">
                    <x-file-uploader name="file" label="File" required help="PDF, JPG, JPEG or PNG up to 5 MB" />
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="is_required" name="is_required">
                        <label class="form-check-label" for="is_required">Mark as mandatory document</label>
                    </div>
                </div>
            </div>
        </x-modal>
    @endcan
@endsection

@push('scripts')
<script type="module">
    const remarkForm = document.getElementById('lead-remark-form');

    remarkForm?.addEventListener('submit', async (event) => {
        event.preventDefault();

        try {
            const payload = await LoanPro.request(remarkForm.action, { method: 'POST', body: new FormData(remarkForm) });
            document.getElementById('lead-remark-list')?.insertAdjacentHTML('afterbegin', payload.html);
            remarkForm.reset();
            LoanPro.toast(payload.message ?? 'Remark added.');
        } catch (error) {
            LoanPro.toast(error.message, 'danger');
        }
    });
</script>
@endpush
