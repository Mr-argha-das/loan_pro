@extends('layouts.app')

@section('title', 'Invoice Management')
@section('page-header', true)
@section('page-title', 'Invoice Management')
@section('page-subtitle', 'Raise, issue and track invoices for fees, premiums and charges.')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Invoices</li>
@endsection

@section('page-actions')
    <div class="d-flex gap-2">
        @can('export', App\Models\Invoice::class)
            <a href="{{ route('invoices.export', request()->query()) }}" class="btn btn-light btn-sm"><i class="bi bi-file-earmark-excel"></i> Export</a>
        @endcan
        @can('create', App\Models\Invoice::class)
            <a href="{{ route('invoices.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Create Invoice</a>
        @endcan
    </div>
@endsection

@section('content')
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3"><x-stat-card label="Total invoices" :value="number_format($stats['count'] ?? 0)" icon="bi-receipt" tone="primary" /></div>
        <div class="col-6 col-lg-3"><x-stat-card label="Invoiced value" :value="\App\Support\Format::compactInr($stats['invoiced'] ?? 0)" icon="bi-currency-rupee" tone="blue" /></div>
        <div class="col-6 col-lg-3"><x-stat-card label="Collected" :value="\App\Support\Format::compactInr($stats['collected'] ?? 0)" icon="bi-cash-stack" tone="green" /></div>
        <div class="col-6 col-lg-3"><x-stat-card label="Outstanding" :value="\App\Support\Format::compactInr($stats['outstanding'] ?? 0)" icon="bi-exclamation-circle" tone="orange"
                          :meta="number_format($stats['overdue_count'] ?? 0).' overdue'" /></div>
    </div>

    <x-filter-panel :action="route('invoices.index')" :reset-url="route('invoices.index')">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label" for="q">Search</label>
                <x-search-box name="q" :value="$filters['q'] ?? null" placeholder="Invoice number or customer" />
            </div>
            <div class="col-md-2">
                <label class="form-label" for="status">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All statuses</option>
                    @foreach (['draft' => 'Draft', 'issued' => 'Issued', 'partially_paid' => 'Partially paid', 'paid' => 'Paid', 'overdue' => 'Overdue', 'cancelled' => 'Cancelled'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? null) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="customer_id">Customer</label>
                <select class="form-select" id="customer_id" name="customer_id">
                    <option value="">All customers</option>
                    @foreach ($customers as $id => $name)
                        <option value="{{ $id }}" @selected((int) ($filters['customer_id'] ?? 0) === (int) $id)>{{ $name }}</option>
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
        <div id="invoice-table-body">
            @include('finance.invoices.partials.table', ['items' => $invoices])
        </div>
    </x-card>
@endsection

@push('scripts')
<script type="module">
    LoanPro.bindTableFilters({ url: '{{ route('invoices.index') }}', container: '#invoice-table-body' });
</script>
@endpush
