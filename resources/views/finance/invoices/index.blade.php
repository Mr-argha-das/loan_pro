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
<style>
    .lp-stat {
    min-width: 0;
     background: linear-gradient(135deg, #1689ed, #0871e8)!important;
    border: 1px solid var(--lp-border);
    border-radius: var(--lp-radius);
    padding: 18px;
    height: 100%;
    box-shadow: var(--lp-shadow-xs);
    transition: box-shadow var(--lp-transition), transform var(--lp-transition);
}

/* Gradients */
.card-blue {
    background: linear-gradient(135deg, #1689ed, #0871e8)!important;
}

.card-green {
    background: linear-gradient(135deg, #35ca72, #0cae5c)!important;
}

.card-purple {
    background: linear-gradient(135deg, #9550ed, #7b35df)!important;
}

.card-orange {
    background: linear-gradient(135deg, #ffc536, #ff9e19)!important;
}

.card-red {
    background: linear-gradient(135deg, #fa3d4e, #ed1c35)!important;
}

.card-cyan {
    background: linear-gradient(135deg, #16c7c6, #08a9bd)!important;
}

.card-blue-dark {
    background: linear-gradient(135deg, #4778e8, #2459ca)!important;
}

.card-pink {
    background: linear-gradient(135deg, #ed4c9c, #dd2180)!important;
}

.lp-stat__label{
    color: #fff!important;
}

.lp-stat__value{
   color: #fff!important; 
}

.lp-stat__meta{
     color: #fff!important; 
}

.lp-stat__meta span.fw-semibold.text-success{
    color: #fff!important; 
}

/* Left Icon */
.lp-stat__icon {
    width: 46px;
    height: 46px;
    min-width: 46px;
    border-radius: 11px;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 23px;
    font-weight: 400;

    background: rgba(255, 255, 255, 0.18)!important;
    border: 1px solid rgba(255, 255, 255, 0.08)!important;
    color: #fff!important;
    box-shadow: inset 0 1px 5px rgba(255, 255, 255, 0.12)!important;
}
    
</style>
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3"><x-stat-card label="Total invoices" :value="number_format($stats['count'] ?? 0)" icon="bi-receipt" tone="primary" class="card-blue" /></div>
        <div class="col-6 col-lg-3"><x-stat-card label="Invoiced value" :value="\App\Support\Format::compactInr($stats['invoiced'] ?? 0)" icon="bi-currency-rupee" tone="blue" class="card-green" /></div>
        <div class="col-6 col-lg-3"><x-stat-card label="Collected" :value="\App\Support\Format::compactInr($stats['collected'] ?? 0)" icon="bi-cash-stack" tone="green" class="card-purple" /></div>
        <div class="col-6 col-lg-3"><x-stat-card label="Outstanding" :value="\App\Support\Format::compactInr($stats['outstanding'] ?? 0)" icon="bi-exclamation-circle" tone="orange" class="card-red"
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
