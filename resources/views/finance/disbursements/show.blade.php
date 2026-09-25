@extends('layouts.app')

@section('title', $disbursement->disbursement_code)
@section('page-header', true)
@section('page-title', 'Disbursement '.$disbursement->disbursement_code)
@section('page-subtitle', ($disbursement->customer?->name ?? 'Customer').' · '.($disbursement->lender?->name ?? 'Lender pending'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('disbursements.index') }}">Disbursements</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $disbursement->disbursement_code }}</li>
@endsection

@section('page-actions')
    <div class="d-flex flex-wrap gap-2">
        @can('update', $disbursement)
            <a href="{{ route('disbursements.edit', $disbursement) }}" class="btn btn-light btn-sm"><i class="bi bi-pencil"></i> Edit</a>
            @if ($disbursement->status !== 'completed')
                <form method="POST" action="{{ route('disbursements.status', $disbursement) }}" class="d-inline" data-confirm="Mark this disbursement as completed?">
                    @csrf
                    <input type="hidden" name="status" value="completed">
                    <button class="btn btn-success btn-sm"><i class="bi bi-check2-circle"></i> Mark completed</button>
                </form>
            @endif
        @endcan
    </div>
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-lg-8">
            <x-card title="Disbursement details" icon="bi-cash-stack">
                <div class="row g-3 mb-3">
                    <div class="col-6 col-md-3"><x-stat-card label="Approved" :value="\App\Support\Format::compactInr($disbursement->approved_amount)" icon="bi-patch-check" tone="blue" /></div>
                    <div class="col-6 col-md-3"><x-stat-card label="Disbursed" :value="\App\Support\Format::compactInr($disbursement->disbursed_amount)" icon="bi-cash-stack" tone="green" /></div>
                    <div class="col-6 col-md-3"><x-stat-card label="Charges" :value="\App\Support\Format::compactInr((float) $disbursement->processing_fee + (float) $disbursement->other_charges)" icon="bi-scissors" tone="orange" /></div>
                    <div class="col-6 col-md-3"><x-stat-card label="Net payable" :value="\App\Support\Format::compactInr($disbursement->net_amount)" icon="bi-wallet2" tone="primary" /></div>
                </div>

                <div class="row g-2 small">
                    <div class="col-6 col-md-4"><span class="text-muted">Application:</span>
                        <span class="fw-semibold">
                            @if ($disbursement->application)<a href="{{ route('applications.show', $disbursement->application) }}">{{ $disbursement->application->application_code }}</a>@else — @endif
                        </span>
                    </div>
                    <div class="col-6 col-md-4"><span class="text-muted">Date:</span> <span class="fw-semibold">{{ \App\Support\Format::date($disbursement->disbursement_date) }}</span></div>
                    <div class="col-6 col-md-4"><span class="text-muted">Mode:</span> <span class="fw-semibold">{{ strtoupper($disbursement->mode ?? '—') }}</span></div>
                    <div class="col-6 col-md-4"><span class="text-muted">Reference:</span> <span class="fw-semibold">{{ $disbursement->reference_number ?? '—' }}</span></div>
                    <div class="col-6 col-md-4"><span class="text-muted">UTR:</span> <span class="fw-semibold">{{ $disbursement->utr_number ?? '—' }}</span></div>
                    <div class="col-6 col-md-4"><span class="text-muted">Status:</span> <x-status-badge :status="$disbursement->status" /></div>
                    <div class="col-6 col-md-4"><span class="text-muted">Bank:</span> <span class="fw-semibold">{{ $disbursement->bank_name ?? '—' }}</span></div>
                    <div class="col-6 col-md-4"><span class="text-muted">Account:</span> <span class="fw-semibold">{{ $disbursement->bank_account_number ? \App\Support\Format::mask($disbursement->bank_account_number, 4) : '—' }}</span></div>
                    <div class="col-6 col-md-4"><span class="text-muted">IFSC:</span> <span class="fw-semibold">{{ $disbursement->bank_ifsc ?? '—' }}</span></div>
                    <div class="col-6 col-md-4"><span class="text-muted">Created by:</span> <span class="fw-semibold">{{ $disbursement->creator?->name ?? '—' }}</span></div>
                    <div class="col-6 col-md-4"><span class="text-muted">Approved by:</span> <span class="fw-semibold">{{ $disbursement->approver?->name ?? '—' }}</span></div>
                    <div class="col-6 col-md-4"><span class="text-muted">Completed:</span> <span class="fw-semibold">{{ \App\Support\Format::dateTime($disbursement->completed_at) }}</span></div>
                </div>

                @if ($disbursement->remarks)
                    <div class="lp-divider"></div>
                    <div class="small"><strong>Remarks:</strong> {{ $disbursement->remarks }}</div>
                @endif
            </x-card>

            <x-card title="Documents" icon="bi-folder-check" class="mt-4">
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
                            @forelse ($disbursement->documents as $document)
                                <tr>
                                    <td class="fw-semibold">{{ $document->documentType?->name ?? 'Document' }}</td>
                                    <td><x-status-badge :status="$document->status" /></td>
                                    <td class="text-muted text-nowrap">{{ \App\Support\Format::date($document->created_at) }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('documents.preview', $document) }}" target="_blank" rel="noopener" class="btn btn-sm btn-light"><i class="bi bi-eye"></i></a>
                                        <a href="{{ route('documents.download', $document) }}" class="btn btn-sm btn-light"><i class="bi bi-download"></i></a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4"><x-empty-state icon="bi-folder2-open" title="No documents" message="Attach the sanction letter and bank proof." /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>

        <div class="col-lg-4">
            @can('update', $disbursement)
                <x-card title="Update status" icon="bi-arrow-repeat">
                    <form method="POST" action="{{ route('disbursements.status', $disbursement) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="disbursement_status">Status</label>
                            <select class="form-select" id="disbursement_status" name="status" required>
                                @foreach (['pending' => 'Pending', 'processing' => 'Processing', 'completed' => 'Completed', 'failed' => 'Failed', 'cancelled' => 'Cancelled'] as $value => $label)
                                    <option value="{{ $value }}" @selected($disbursement->status === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3"><x-textarea name="remarks" label="Note" rows="2" /></div>
                        <button class="btn btn-primary w-100"><i class="bi bi-check2"></i> Save status</button>
                    </form>
                </x-card>
            @endcan

            @if ($disbursement->application)
                <x-card title="Loan file" icon="bi-clipboard-data" class="mt-4">
                    <div class="lp-kv"><span class="lp-kv__label">Loan amount</span><span class="lp-kv__value">{{ \App\Support\Format::money($disbursement->application->loan_amount) }}</span></div>
                    <div class="lp-kv"><span class="lp-kv__label">ROI</span><span class="lp-kv__value">{{ $disbursement->application->roi ?? '—' }}%</span></div>
                    <div class="lp-kv"><span class="lp-kv__label">Tenure</span><span class="lp-kv__value">{{ \App\Support\Format::tenure($disbursement->application->tenure_months) }}</span></div>
                    <div class="lp-kv"><span class="lp-kv__label">EMI</span><span class="lp-kv__value">{{ \App\Support\Format::money($disbursement->application->emi) }}</span></div>
                    <a href="{{ route('applications.show', $disbursement->application) }}" class="btn btn-light w-100 mt-2"><i class="bi bi-box-arrow-up-right"></i> Open application</a>
                </x-card>
            @endif
        </div>
    </div>
@endsection
