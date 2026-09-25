@extends('layouts.app')

@section('title', 'Lead List')
@section('page-header', true)
@section('page-title', 'Lead Management')
@section('page-subtitle', 'Every lead you own or created, with live status tracking.')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Leads</li>
@endsection

@section('page-actions')
    @canPermission('leads.export')
        <a href="{{ route('leads.export', request()->query()) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-file-earmark-excel"></i> Export</a>
    @endcanPermission
    @canPermission('leads.create')
        <a href="{{ route('leads.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Create Lead</a>
    @endcanPermission
@endsection

@section('content')
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-2"><x-stat-card label="Total" :value="number_format($stats['total'])" icon="bi-collection" tone="primary" /></div>
        <div class="col-6 col-lg-2"><x-stat-card label="Draft" :value="number_format($stats['draft'])" icon="bi-pencil" tone="secondary" /></div>
        <div class="col-6 col-lg-2"><x-stat-card label="New" :value="number_format($stats['new'])" icon="bi-stars" tone="info" /></div>
        <div class="col-6 col-lg-2"><x-stat-card label="Verified" :value="number_format($stats['verified'])" icon="bi-patch-check" tone="success" /></div>
        <div class="col-6 col-lg-2"><x-stat-card label="Converted" :value="number_format($stats['converted'])" icon="bi-arrow-repeat" tone="warning" /></div>
        <div class="col-6 col-lg-2"><x-stat-card label="Closed" :value="number_format($stats['rejected'])" icon="bi-x-circle" tone="danger" /></div>
    </div>

    <x-filter-panel>
        <div class="col-md-3">
            <label class="form-label" for="q">Search</label>
            <input type="search" class="form-control" id="q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Lead ID, customer, mobile...">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="status">Status</label>
            <select class="form-select" id="status" name="status">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->slug }}" @selected(($filters['status'] ?? '') === $status->slug)>{{ $status->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="product_id">Product</label>
            <select class="form-select" id="product_id" name="product_id">
                <option value="">All products</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}" @selected(($filters['product_id'] ?? '') == $product->id)>{{ $product->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="category_id">Category</label>
            <select class="form-select" id="category_id" name="category_id">
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(($filters['category_id'] ?? '') == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="assigned_to">Owner</label>
            <select class="form-select" id="assigned_to" name="assigned_to">
                <option value="">Everyone</option>
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}" @selected(($filters['assigned_to'] ?? '') == $employee->id)>{{ $employee->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="from_date">From</label>
            <input type="date" class="form-control" id="from_date" name="from_date" value="{{ $filters['from_date'] ?? '' }}">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="to_date">To</label>
            <input type="date" class="form-control" id="to_date" name="to_date" value="{{ $filters['to_date'] ?? '' }}">
        </div>
    </x-filter-panel>

    <div id="lead-table">
        @include('leads.partials.table')
    </div>

    <x-modal id="quick-view-modal" title="Lead overview" size="lg" submit-label="Close" action="{{ url()->current() }}">
        <div id="quick-view-body"><div class="lp-skeleton" style="height:200px"></div></div>
    </x-modal>

    <x-modal id="status-modal" title="Change lead status" submit-label="Update status">
        <div class="mb-3">
            <label class="form-label" for="modal-lead-status">New status<span class="req">*</span></label>
            <select class="form-select" id="modal-lead-status" name="lead_status_id" required>
                @foreach ($statuses as $status)
                    <option value="{{ $status->id }}">{{ $status->name }}</option>
                @endforeach
            </select>
        </div>
        <x-textarea name="note" label="Remark" rows="3" placeholder="Why is the status changing?" />
    </x-modal>

    <x-modal id="assign-modal" title="Assign lead" submit-label="Assign">
        <x-select name="assigned_to" label="Assign to" required :options="$employees" placeholder="Select employee" />
        <x-textarea name="note" label="Note" rows="2" />
    </x-modal>
@endsection

@push('scripts')
<script type="module">
    const tableUrl = '{{ route('leads.index') }}';

    // Server-side table refresh (AJAX + fetch) - pagination and filters stay in sync.
    async function refresh() {
        const params = new URLSearchParams(new FormData(document.querySelector('.lp-filter-panel form')));
        await LoanPro.reloadTable(tableUrl, '#lead-table', Object.fromEntries(params));
        bindRowActions();
    }

    function bindRowActions() {
        document.querySelectorAll('[data-quick-view]').forEach((button) => {
            button.addEventListener('click', async () => {
                const payload = await LoanPro.request(button.dataset.quickView);
                document.getElementById('quick-view-body').innerHTML = payload.html;
                bootstrap.Modal.getOrCreateInstance(document.getElementById('quick-view-modal')).show();
            });
        });

        document.querySelectorAll('[data-status-modal]').forEach((button) => {
            button.addEventListener('click', () => {
                const modal = document.getElementById('status-modal');
                modal.querySelector('form').action = button.dataset.statusModal;
                bootstrap.Modal.getOrCreateInstance(modal).show();
            });
        });

        document.querySelectorAll('[data-assign-modal]').forEach((button) => {
            button.addEventListener('click', () => {
                const modal = document.getElementById('assign-modal');
                modal.querySelector('form').action = button.dataset.assignModal;
                bootstrap.Modal.getOrCreateInstance(modal).show();
            });
        });

        document.querySelectorAll('#lead-table .pagination a').forEach((link) => {
            link.addEventListener('click', async (event) => {
                event.preventDefault();
                await LoanPro.reloadTable(link.href, '#lead-table');
                bindRowActions();
            });
        });
    }

    document.querySelector('.lp-filter-panel form')?.addEventListener('submit', (event) => {
        event.preventDefault();
        refresh();
    });

    bindRowActions();
</script>
@endpush
