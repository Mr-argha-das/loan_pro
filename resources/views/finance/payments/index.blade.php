@extends('layouts.app')

@section('title', 'Payment Management')
@section('page-header', true)
@section('page-title', 'Payment Management')
@section('page-subtitle', 'Collections across cash, bank transfer, UPI, card and cheque.')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Payments</li>
@endsection

@section('page-actions')
    <div class="d-flex gap-2">
        @can('export', App\Models\Payment::class)
            <a href="{{ route('payments.export', request()->query()) }}" class="btn btn-light btn-sm"><i class="bi bi-file-earmark-excel"></i> Export</a>
        @endcan
        @can('create', App\Models\Payment::class)
            <a href="{{ route('payments.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Record Payment</a>
        @endcan
    </div>
@endsection

@section('content')
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3"><x-stat-card label="Collected today" :value="\App\Support\Format::compactInr($stats['today'] ?? 0)" icon="bi-calendar-check" tone="primary" /></div>
        <div class="col-6 col-lg-3"><x-stat-card label="This month" :value="\App\Support\Format::compactInr($stats['month'] ?? 0)" icon="bi-graph-up-arrow" tone="blue" /></div>
        <div class="col-6 col-lg-3"><x-stat-card label="Lifetime collections" :value="\App\Support\Format::compactInr($stats['total'] ?? 0)" icon="bi-cash-stack" tone="green" /></div>
        <div class="col-6 col-lg-3"><x-stat-card label="Payments recorded" :value="number_format($payments->total())" icon="bi-receipt" tone="orange" /></div>
    </div>

    <x-filter-panel :action="route('payments.index')" :reset-url="route('payments.index')">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label" for="q">Search</label>
                <x-search-box name="q" :value="$filters['q'] ?? null" placeholder="Reference, transaction or customer" />
            </div>
            <div class="col-md-2">
                <label class="form-label" for="status">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All statuses</option>
                    @foreach (['pending' => 'Pending', 'completed' => 'Completed', 'failed' => 'Failed', 'refunded' => 'Refunded'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? null) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="payment_method_id">Mode</label>
                <select class="form-select" id="payment_method_id" name="payment_method_id">
                    <option value="">All modes</option>
                    @foreach ($methods as $method)
                        <option value="{{ $method->id }}" @selected((int) ($filters['payment_method_id'] ?? 0) === $method->id)>{{ $method->name }}</option>
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
        <div id="payment-table-body">
            @include('finance.payments.partials.table', ['items' => $payments])
        </div>
    </x-card>
@endsection

@push('scripts')
<script type="module">
    LoanPro.bindTableFilters({ url: '{{ route('payments.index') }}', container: '#payment-table-body' });
</script>
@endpush
