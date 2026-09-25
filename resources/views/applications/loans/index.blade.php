@extends('layouts.app')

@section('title', 'Loan Applications')
@section('page-header', true)
@section('page-title', 'Loan Applications')
@section('page-subtitle', 'Every loan file, from submission through sanction and disbursal.')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Loan Applications</li>
@endsection

@section('page-actions')
    <div class="d-flex gap-2">
        @can('export', App\Models\LoanApplication::class)
            <a href="{{ route('applications.export', request()->query()) }}" class="btn btn-light btn-sm"><i class="bi bi-file-earmark-excel"></i> Export</a>
        @endcan
        @can('create', App\Models\LoanApplication::class)
            <a href="{{ route('applications.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> New Application</a>
        @endcan
    </div>
@endsection

@section('content')
    <x-filter-panel :action="route('applications.index')" :reset-url="route('applications.index')">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label" for="q">Search</label>
                <x-search-box name="q" :value="$filters['q'] ?? null" placeholder="Application ID, customer or mobile" />
            </div>
            <div class="col-md-2">
                <label class="form-label" for="status">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->slug }}" @selected(($filters['status'] ?? null) === $status->slug)>{{ $status->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="category_id">Category</label>
                <select class="form-select" id="category_id" name="category_id">
                    <option value="">All categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((int) ($filters['category_id'] ?? 0) === $category->id)>{{ $category->name }}</option>
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
            <div class="col-md-2">
                <label class="form-label" for="assigned_to">Owner</label>
                <select class="form-select" id="assigned_to" name="assigned_to">
                    <option value="">Everyone</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}" @selected((int) ($filters['assigned_to'] ?? 0) === $employee->id)>{{ $employee->name }}</option>
                    @endforeach
                </select>
            </div>
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
        <div id="application-table-body">
            @include('applications.loans.partials.table', ['items' => $applications])
        </div>
    </x-card>

    <x-modal id="application-quick-view-modal" title="Application summary" submit-label="Open file" :action="null" size="lg">
        <div id="application-quick-view-body" class="text-center py-4 text-muted">
            <span class="spinner-border spinner-border-sm"></span> Loading…
        </div>
        <x-slot:footer></x-slot:footer>
    </x-modal>
@endsection

@push('scripts')
<script type="module">
    const table = LoanPro.bindTableFilters({
        url: '{{ route('applications.index') }}',
        container: '#application-table-body',
        afterReload: () => bindQuickView(),
    });

    function bindQuickView() {
        document.querySelectorAll('[data-application-quick-view]').forEach((button) => {
            button.addEventListener('click', async () => {
                const body = document.getElementById('application-quick-view-body');
                body.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Loading…';

                const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('application-quick-view-modal'));
                modal.show();

                try {
                    const payload = await LoanPro.request(button.dataset.applicationQuickView);
                    body.innerHTML = payload.html;
                } catch (error) {
                    body.innerHTML = `<div class="text-danger">${error.message}</div>`;
                }
            });
        });
    }

    bindQuickView();
</script>
@endpush
