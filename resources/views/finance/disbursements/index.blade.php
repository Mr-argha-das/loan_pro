@extends('layouts.app')

@section('title', 'Disbursement')
@section('page-header', true)
@section('page-title', 'Disbursement Management')
@section('page-subtitle', 'Track funds released to borrowers, from approval through completion.')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Disbursements</li>
@endsection

@section('page-actions')
    <div class="d-flex gap-2">
        @can('export', App\Models\Disbursement::class)
            <a href="{{ route('disbursements.export', request()->query()) }}" class="btn btn-light btn-sm"><i class="bi bi-file-earmark-excel"></i> Export</a>
        @endcan
        @can('create', App\Models\Disbursement::class)
            <a href="{{ route('disbursements.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> New Disbursement</a>
        @endcan
    </div>
@endsection

@section('content')
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3"><x-stat-card label="Total disbursed" :value="\App\Support\Format::compactInr($stats['total'] ?? 0)" icon="bi-cash-stack" tone="green" /></div>
        <div class="col-6 col-lg-3"><x-stat-card label="Pending" :value="number_format($stats['pending'] ?? 0)" icon="bi-hourglass-split" tone="orange" /></div>
        <div class="col-6 col-lg-3"><x-stat-card label="Processing" :value="number_format($stats['processing'] ?? 0)" icon="bi-arrow-repeat" tone="blue" /></div>
        <div class="col-6 col-lg-3"><x-stat-card label="Completed this month" :value="\App\Support\Format::compactInr($stats['month'] ?? 0)" icon="bi-calendar-check" tone="primary" /></div>
    </div>

    <x-filter-panel :action="route('disbursements.index')" :reset-url="route('disbursements.index')">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label" for="q">Search</label>
                <x-search-box name="q" :value="$filters['q'] ?? null" placeholder="Disbursement code, UTR or customer" />
            </div>
            <div class="col-md-2">
                <label class="form-label" for="status">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All statuses</option>
                    @foreach (['pending' => 'Pending', 'processing' => 'Processing', 'completed' => 'Completed', 'failed' => 'Failed', 'cancelled' => 'Cancelled'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? null) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="lender_id">Lender</label>
                <select class="form-select" id="lender_id" name="lender_id">
                    <option value="">All lenders</option>
                    @foreach ($lenders as $lender)
                        <option value="{{ $lender->id }}" @selected((int) ($filters['lender_id'] ?? 0) === $lender->id)>{{ $lender->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><label class="form-label" for="from_date">From</label><input type="date" class="form-control" id="from_date" name="from_date" value="{{ $filters['from_date'] ?? '' }}"></div>
            <div class="col-md-2"><label class="form-label" for="to_date">To</label><input type="date" class="form-control" id="to_date" name="to_date" value="{{ $filters['to_date'] ?? '' }}"></div>
            <div class="col-md-1">
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
        <div id="disbursement-table-body">
            @include('finance.disbursements.partials.table', ['items' => $disbursements])
        </div>
    </x-card>
@endsection

@push('scripts')
<script type="module">
    LoanPro.bindTableFilters({ url: '{{ route('disbursements.index') }}', container: '#disbursement-table-body' });
</script>
@endpush
