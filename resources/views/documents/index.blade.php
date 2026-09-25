@extends('layouts.app')

@section('title', 'Documents')
@section('page-header', true)
@section('page-title', 'Document Vault')
@section('page-subtitle', 'Every uploaded proof, stored privately with verification workflow.')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Documents</li>
@endsection

@section('page-actions')
    @can('create', App\Models\Document::class)
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#document-upload-modal">
            <i class="bi bi-cloud-arrow-up"></i> Upload Document
        </button>
    @endcan
@endsection

@section('content')
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3"><x-stat-card label="Total documents" :value="number_format($stats['total'])" icon="bi-folder" tone="primary" /></div>
        <div class="col-6 col-lg-3"><x-stat-card label="Awaiting verification" :value="number_format($stats['pending'])" icon="bi-hourglass-split" tone="orange" /></div>
        <div class="col-6 col-lg-3"><x-stat-card label="Verified" :value="number_format($stats['verified'])" icon="bi-patch-check" tone="green" /></div>
        <div class="col-6 col-lg-3"><x-stat-card label="Rejected" :value="number_format($stats['rejected'])" icon="bi-x-octagon" tone="red" /></div>
    </div>

    <x-filter-panel :action="route('documents.index')" :reset-url="route('documents.index')">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label" for="q">Search</label>
                <x-search-box name="q" :value="$filters['q'] ?? null" placeholder="File name or document number" />
            </div>
            <div class="col-md-3">
                <label class="form-label" for="document_type_id">Document type</label>
                <select class="form-select" id="document_type_id" name="document_type_id">
                    <option value="">All types</option>
                    @foreach ($documentTypes as $type)
                        <option value="{{ $type->id }}" @selected((int) ($filters['document_type_id'] ?? 0) === $type->id)>{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="status">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All</option>
                    @foreach (['uploaded' => 'Uploaded', 'pending' => 'Pending', 'verified' => 'Verified', 'rejected' => 'Rejected'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? null) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="per_page">Rows</label>
                <select class="form-select" id="per_page" name="per_page">
                    @foreach (config('loanpro.pagination.options', [10, 25, 50, 100]) as $option)
                        <option value="{{ $option }}" @selected((int) ($filters['per_page'] ?? 25) === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-filter-panel>

    <x-card :padding="false">
        <div id="document-table-body">
            @include('documents.partials.table', ['items' => $documents])
        </div>
    </x-card>

    @can('create', App\Models\Document::class)
        <x-modal id="document-upload-modal" title="Upload document" :action="route('documents.store')" submit-label="Upload" size="lg">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="documentable_type">Attach to<span class="req">*</span></label>
                    <select class="form-select" id="documentable_type" name="documentable_type" required>
                        <option value="lead">Lead</option>
                        <option value="customer">Customer</option>
                        <option value="loan_application">Loan application</option>
                        <option value="insurance_application">Insurance application</option>
                        <option value="invoice">Invoice</option>
                        <option value="disbursement">Disbursement</option>
                    </select>
                </div>
                <div class="col-md-4"><x-input name="documentable_id" label="Record ID" type="number" required help="Numeric id of the record above" /></div>
                <div class="col-md-4">
                    <label class="form-label" for="document_type_id">Document type<span class="req">*</span></label>
                    <select class="form-select" id="document_type_id" name="document_type_id" required>
                        @foreach ($documentTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4"><x-input name="issued_number" label="Document number" /></div>
                <div class="col-md-4"><x-input name="expires_at" label="Expiry date" type="date" /></div>
                <div class="col-md-4">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" value="1" id="is_required" name="is_required">
                        <label class="form-check-label" for="is_required">Mandatory document</label>
                    </div>
                </div>
                <div class="col-12"><x-file-uploader name="file" label="File" required /></div>
            </div>
        </x-modal>
    @endcan
@endsection

@push('scripts')
<script type="module">
    LoanPro.bindTableFilters({ url: '{{ route('documents.index') }}', container: '#document-table-body' });
</script>
@endpush
