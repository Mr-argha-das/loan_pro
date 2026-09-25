@extends('layouts.app')

@section('title', 'Lenders & Insurers')
@section('page-header', true)
@section('page-title', 'Lenders & Insurers')
@section('page-subtitle', 'Partner banks, NBFCs and insurers with their product offers.')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Lenders</li>
@endsection

@section('content')
    <x-filter-panel :action="route('lenders.index')" :reset-url="route('lenders.index')">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label" for="q">Search</label>
                <x-search-box name="q" :value="$filters['q'] ?? null" placeholder="Lender name or code" />
            </div>
            <div class="col-md-3">
                <label class="form-label" for="lender_type">Type</label>
                <select class="form-select" id="lender_type" name="lender_type">
                    <option value="">All types</option>
                    @foreach (['bank' => 'Bank', 'nbfc' => 'NBFC', 'insurer' => 'Insurer', 'fintech' => 'Fintech'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['lender_type'] ?? null) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="status">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All</option>
                    <option value="active" @selected(($filters['status'] ?? null) === 'active')>Active</option>
                    <option value="inactive" @selected(($filters['status'] ?? null) === 'inactive')>Inactive</option>
                </select>
            </div>
        </div>
    </x-filter-panel>

    <x-card :padding="false">
        <div id="lender-table-body">
            @include('products.lenders.partials.table', ['items' => $lenders])
        </div>
    </x-card>
@endsection

@push('scripts')
<script type="module">
    LoanPro.bindTableFilters({ url: '{{ route('lenders.index') }}', container: '#lender-table-body' });
</script>
@endpush
