@extends('layouts.app')

@section('title', 'Insurance Management')
@section('page-header', true)
@section('page-title', 'Insurance Management')
@section('page-subtitle', 'Life, health and general insurance applications with premium tracking.')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Insurance</li>
@endsection

@section('page-actions')
    @can('create', App\Models\LoanApplication::class)
        <a href="{{ route('insurance.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> New Policy Application</a>
    @endcan
@endsection

@section('content')
    <x-filter-panel :action="route('insurance.index')" :reset-url="route('insurance.index')">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label" for="q">Search</label>
                <x-search-box name="q" :value="$filters['q'] ?? null" placeholder="Application ID, policy number or customer" />
            </div>
            <div class="col-md-3">
                <label class="form-label" for="status">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->slug }}" @selected(($filters['status'] ?? null) === $status->slug)>{{ $status->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="per_page">Rows</label>
                <select class="form-select" id="per_page" name="per_page">
                    @foreach (config('loanpro.pagination.options', [10, 25, 50, 100]) as $option)
                        <option value="{{ $option }}" @selected((int) ($filters['per_page'] ?? 25) === $option)>{{ $option }} per page</option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-filter-panel>

    <x-card :padding="false">
        <div id="insurance-table-body">
            @include('applications.insurance.partials.table', ['items' => $applications])
        </div>
    </x-card>
@endsection

@push('scripts')
<script type="module">
    LoanPro.bindTableFilters({ url: '{{ route('insurance.index') }}', container: '#insurance-table-body' });
</script>
@endpush
