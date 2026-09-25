@extends('layouts.app')

@section('title', 'Application '.$application->application_code)
@section('page-header', true)
@section('page-title', 'Application '.$application->application_code)
@section('page-subtitle', ($application->customer?->name ?? 'Customer').' · '.($application->category?->name ?? 'Loan').' · '.($application->lender?->name ?? 'Lender pending'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('applications.index') }}">Loan Applications</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $application->application_code }}</li>
@endsection

@section('page-actions')
    <div class="d-flex flex-wrap gap-2">
        @can('update', $application)
            <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#status-application-modal"
                    data-status-url="{{ route('applications.status', $application) }}">
                <i class="bi bi-arrow-repeat"></i> Change Status
            </button>
        @endcan
        @can('approve', $application)
            <form method="POST" action="{{ route('applications.status', $application) }}" class="d-inline" data-confirm="Approve this application?">
                @csrf
                <input type="hidden" name="status_slug" value="approved">
                <button class="btn btn-success btn-sm"><i class="bi bi-check2-circle"></i> Approve</button>
            </form>
        @endcan
        @canPermission('disbursements.create')
            @if (! $disbursement)
                <a href="{{ route('disbursements.create', ['loan_application_id' => $application->id]) }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-cash-stack"></i> Disburse
                </a>
            @endif
        @endcanPermission
    </div>
@endsection

@section('content')
    <div class="lp-card mb-4">
        <div class="lp-card__body">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="d-flex align-items-center gap-3">
                        <span class="lp-avatar lp-avatar--lg">{{ $application->customer?->initials() }}</span>
                        <div>
                            <div class="fw-semibold">{{ $application->customer?->name }}</div>
                            <div class="text-muted small">{{ $application->customer?->mobile }} &middot; {{ $application->customer?->email ?? 'No email' }}</div>
                            <div class="mt-2"><x-status-badge :status="$application->status" :label="$application->applicationStatus?->name" /></div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="row g-3">
                        <div class="col-6 col-md-3"><x-stat-card label="Loan amount" :value="\App\Support\Format::money($application->loan_amount)" icon="bi-cash-coin" tone="primary" /></div>
                        <div class="col-6 col-md-3"><x-stat-card label="EMI" :value="\App\Support\Format::money($application->emi)" icon="bi-calendar-check" tone="blue" /></div>
                        <div class="col-6 col-md-3"><x-stat-card label="Sanctioned" :value="\App\Support\Format::compactInr($application->sanctioned_amount)" icon="bi-patch-check" tone="green" /></div>
                        <div class="col-6 col-md-3"><x-stat-card label="Disbursed" :value="\App\Support\Format::compactInr($application->disbursed_amount)" icon="bi-wallet2" tone="orange" /></div>
                    </div>
                </div>
            </div>

            <div class="lp-divider"></div>

            <div class="row g-2 small">
                <div class="col-6 col-md-3"><span class="text-muted">Lender:</span> <span class="fw-semibold">{{ $application->lender?->name ?? '—' }}</span></div>
                <div class="col-6 col-md-3"><span class="text-muted">Category:</span> <span class="fw-semibold">{{ $application->category?->name ?? '—' }}</span></div>
                <div class="col-6 col-md-3"><span class="text-muted">Purpose:</span> <span class="fw-semibold">{{ $application->subcategory?->name ?? '—' }}</span></div>
                <div class="col-6 col-md-3"><span class="text-muted">ROI / APR:</span> <span class="fw-semibold">{{ $application->roi ?? '—' }}% / {{ $application->apr ?? '—' }}%</span></div>
                <div class="col-6 col-md-3"><span class="text-muted">Tenure:</span> <span class="fw-semibold">{{ \App\Support\Format::tenure($application->tenure_months) }}</span></div>
                <div class="col-6 col-md-3"><span class="text-muted">Processing fee:</span> <span class="fw-semibold">{{ \App\Support\Format::money($application->processing_fee) }}</span></div>
                <div class="col-6 col-md-3"><span class="text-muted">Source lead:</span>
                    <span class="fw-semibold">
                        @if ($application->lead)
                            <a href="{{ route('leads.show', $application->lead) }}">{{ $application->lead->lead_code }}</a>
                        @else — @endif
                    </span>
                </div>
                <div class="col-6 col-md-3"><span class="text-muted">Owner:</span> <span class="fw-semibold">{{ $application->assignee?->name ?? '—' }}</span></div>
            </div>
        </div>
    </div>

    <x-tabs id="application-tabs" :tabs="[
        'overview' => ['label' => 'Overview', 'icon' => 'bi-grid'],
        'lenders' => ['label' => 'Lender Submissions', 'icon' => 'bi-bank', 'count' => $application->lenders->count()],
        'documents' => ['label' => 'Documents', 'icon' => 'bi-folder-check', 'count' => $application->documents->count()],
        'finance' => ['label' => 'Disbursement & Invoices', 'icon' => 'bi-wallet2'],
        'history' => ['label' => 'Status History', 'icon' => 'bi-clock-history'],
        'remarks' => ['label' => 'Remarks', 'icon' => 'bi-chat-left-text', 'count' => $application->remarks->count()],
    ]">
        <div class="tab-content lp-tab-content">
            <div class="tab-pane fade show active" id="application-tabs-overview" role="tabpanel">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <x-card title="Applicant" icon="bi-person-vcard">
                            <div class="lp-kv"><span class="lp-kv__label">Employment</span><span class="lp-kv__value">{{ $application->employment_type ?? $application->customer?->employmentType?->name ?? '—' }}</span></div>
                            <div class="lp-kv"><span class="lp-kv__label">Monthly income</span><span class="lp-kv__value">{{ \App\Support\Format::money($application->monthly_income) }}</span></div>
                            <div class="lp-kv"><span class="lp-kv__label">Existing EMI</span><span class="lp-kv__value">{{ \App\Support\Format::money($application->existing_emi) }}</span></div>
                            <div class="lp-kv"><span class="lp-kv__label">Credit score</span><span class="lp-kv__value">{{ $application->credit_score ?? '—' }}</span></div>
                            <div class="lp-kv"><span class="lp-kv__label">Document verification</span><span class="lp-kv__value">{{ $application->is_document_verified ? 'Verified' : \App\Support\Format::titleCase($application->verification_status ?? 'pending') }}</span></div>
                        </x-card>
                    </div>

                    <div class="col-lg-6">
                        <x-card title="Key dates" icon="bi-calendar3">
                            <div class="lp-kv"><span class="lp-kv__label">Created</span><span class="lp-kv__value">{{ \App\Support\Format::dateTime($application->created_at) }}</span></div>
                            <div class="lp-kv"><span class="lp-kv__label">Submitted</span><span class="lp-kv__value">{{ \App\Support\Format::dateTime($application->submitted_at) }}</span></div>
                            <div class="lp-kv"><span class="lp-kv__label">Verified</span><span class="lp-kv__value">{{ \App\Support\Format::dateTime($application->verified_at) }}</span></div>
                            <div class="lp-kv"><span class="lp-kv__label">Approved</span><span class="lp-kv__value">{{ \App\Support\Format::dateTime($application->approved_at) }}</span></div>
                            <div class="lp-kv"><span class="lp-kv__label">Sanction date</span><span class="lp-kv__value">{{ \App\Support\Format::date($application->sanction_date) }}</span></div>
                            <div class="lp-kv"><span class="lp-kv__label">Disbursed</span><span class="lp-kv__value">{{ \App\Support\Format::dateTime($application->disbursed_at) }}</span></div>
                        </x-card>
                    </div>

                    @if ($application->notes)
                        <div class="col-12">
                            <x-card title="Internal notes" icon="bi-sticky">
                                <p class="mb-0 text-muted">{{ $application->notes }}</p>
                            </x-card>
                        </div>
                    @endif
                </div>
            </div>

            <div class="tab-pane fade" id="application-tabs-lenders" role="tabpanel">
                <x-card title="Lender submissions" icon="bi-bank">
                    <div class="table-responsive">
                        <table class="lp-table">
                            <thead>
                                <tr>
                                    <th scope="col">Lender</th>
                                    <th scope="col">Product</th>
                                    <th scope="col">Amount</th>
                                    <th scope="col">ROI</th>
                                    <th scope="col">EMI</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Submitted</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($application->lenders as $lender)
                                    <tr>
                                        <td class="fw-semibold">{{ $lender->lender?->name }}</td>
                                        <td>{{ $lender->lenderProduct?->product_name ?? '—' }}</td>
                                        <td>{{ \App\Support\Format::money($lender->loan_amount) }}</td>
                                        <td>{{ $lender->roi !== null ? $lender->roi.'%' : '—' }}</td>
                                        <td>{{ \App\Support\Format::money($lender->emi) }}</td>
                                        <td><x-status-badge :status="$lender->status ?? 'submitted'" /></td>
                                        <td class="text-muted text-nowrap">{{ \App\Support\Format::date($lender->submitted_at) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7"><x-empty-state icon="bi-bank" title="No lender submissions" message="Submit this file to one or more lenders from the lead wizard." /></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-card>
            </div>

            <div class="tab-pane fade" id="application-tabs-documents" role="tabpanel">
                <x-card title="Documents" icon="bi-folder-check">
                    <div class="table-responsive">
                        <table class="lp-table">
                            <thead>
                                <tr>
                                    <th scope="col">Document</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Uploaded by</th>
                                    <th scope="col">Uploaded</th>
                                    <th scope="col" class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($application->documents as $document)
                                    <tr>
                                        <td class="fw-semibold">{{ $document->documentType?->name ?? 'Document' }}</td>
                                        <td><x-status-badge :status="$document->status" /></td>
                                        <td>{{ $document->uploader?->name ?? '—' }}</td>
                                        <td class="text-muted text-nowrap">{{ \App\Support\Format::date($document->created_at) }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('documents.preview', $document) }}" target="_blank" rel="noopener" class="btn btn-sm btn-light"><i class="bi bi-eye"></i></a>
                                            <a href="{{ route('documents.download', $document) }}" class="btn btn-sm btn-light"><i class="bi bi-download"></i></a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5"><x-empty-state icon="bi-folder2-open" title="No documents" message="Upload KYC and income documents to progress the file." /></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-card>
            </div>

            <div class="tab-pane fade" id="application-tabs-finance" role="tabpanel">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <x-card title="Disbursement" icon="bi-cash-stack">
                            @if ($disbursement)
                                <div class="lp-kv"><span class="lp-kv__label">Reference</span><span class="lp-kv__value"><a href="{{ route('disbursements.show', $disbursement) }}">{{ $disbursement->disbursement_code }}</a></span></div>
                                <div class="lp-kv"><span class="lp-kv__label">Amount</span><span class="lp-kv__value">{{ \App\Support\Format::money($disbursement->disbursed_amount) }}</span></div>
                                <div class="lp-kv"><span class="lp-kv__label">Net payable</span><span class="lp-kv__value">{{ \App\Support\Format::money($disbursement->net_amount) }}</span></div>
                                <div class="lp-kv"><span class="lp-kv__label">Mode</span><span class="lp-kv__value">{{ strtoupper($disbursement->mode ?? '—') }}</span></div>
                                <div class="lp-kv"><span class="lp-kv__label">Status</span><span class="lp-kv__value"><x-status-badge :status="$disbursement->status" /></span></div>
                            @else
                                <x-empty-state icon="bi-cash-stack" title="Not disbursed yet" message="A disbursement record is created once the file is approved." />
                            @endif
                        </x-card>
                    </div>

                    <div class="col-lg-6">
                        <x-card title="Invoices" icon="bi-receipt">
                            <div class="table-responsive">
                                <table class="lp-table">
                                    <thead>
                                        <tr>
                                            <th scope="col">Invoice</th>
                                            <th scope="col">Total</th>
                                            <th scope="col">Balance</th>
                                            <th scope="col">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($application->invoices as $invoice)
                                            <tr>
                                                <td><a href="{{ route('invoices.show', $invoice) }}" class="fw-semibold">{{ $invoice->invoice_number }}</a></td>
                                                <td>{{ \App\Support\Format::money($invoice->total) }}</td>
                                                <td>{{ \App\Support\Format::money($invoice->balance_amount) }}</td>
                                                <td><x-status-badge :status="$invoice->status" /></td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4"><x-empty-state icon="bi-receipt" title="No invoices" message="Processing fees and charges are billed here." /></td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </x-card>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="application-tabs-history" role="tabpanel">
                <x-card title="Status history" icon="bi-clock-history" description="Immutable trail of every status change on this file.">
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
                                @forelse ($application->statusHistories->sortByDesc('created_at') as $history)
                                    <tr>
                                        <td class="fw-semibold">{{ $history->stage ?? '—' }}</td>
                                        <td><x-status-badge :status="$history->status?->slug ?? 'pending'" :label="$history->status?->name" /></td>
                                        <td>{{ $history->changedBy?->name ?? 'System' }}</td>
                                        <td class="text-muted">{{ $history->note ?? '—' }}</td>
                                        <td class="text-muted text-nowrap">{{ \App\Support\Format::dateTime($history->created_at) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5"><x-empty-state icon="bi-clock-history" title="No status history" message="Status changes are recorded automatically." /></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-card>
            </div>

            <div class="tab-pane fade" id="application-tabs-remarks" role="tabpanel">
                <x-card title="Remarks &amp; activity" icon="bi-chat-left-text">
                    <form method="POST" action="{{ route('applications.remarks.store', $application) }}">
                        @csrf
                        <div class="row g-2">
                            <div class="col-md-9"><textarea name="body" class="form-control" rows="2" required placeholder="Add a note for the credit team…"></textarea></div>
                            <div class="col-md-3">
                                <select name="type" class="form-select mb-2">
                                    <option value="comment">Comment</option>
                                    <option value="call">Call</option>
                                    <option value="meeting">Meeting</option>
                                    <option value="note">Note</option>
                                </select>
                                <button class="btn btn-primary w-100"><i class="bi bi-send"></i> Add remark</button>
                            </div>
                        </div>
                    </form>
                    <div class="lp-divider"></div>
                    @forelse ($application->remarks->sortByDesc('created_at') as $remark)
                        @include('leads.partials.remark', ['remark' => $remark])
                    @empty
                        <x-empty-state icon="bi-chat-left-text" title="No remarks yet" message="Log calls and credit notes against this application." />
                    @endforelse
                </x-card>
            </div>
        </div>
    </x-tabs>

    @can('update', $application)
        <x-modal id="status-application-modal" title="Change application status" action="" submit-label="Update status">
            <div class="mb-3">
                <label class="form-label" for="status_slug">New status<span class="req">*</span></label>
                <select class="form-select" id="status_slug" name="status_slug" required>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->slug }}" @selected($application->status === $status->slug)>{{ $status->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="row g-3">
                <div class="col-6"><x-input name="sanctioned_amount" label="Sanctioned amount" type="number" step="1000" :value="$application->sanctioned_amount" /></div>
                <div class="col-6"><x-input name="sanction_date" label="Sanction date" type="date" :value="$application->sanction_date?->toDateString()" /></div>
                <div class="col-12"><x-textarea name="note" label="Note" rows="2" /></div>
            </div>
        </x-modal>
    @endcan
@endsection

@push('scripts')
<script type="module">
    document.querySelectorAll('[data-status-url]').forEach((button) => {
        button.addEventListener('click', () => {
            document.querySelector('#status-application-modal form').action = button.dataset.statusUrl;
        });
    });
</script>
@endpush
