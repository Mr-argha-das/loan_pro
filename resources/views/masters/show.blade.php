@extends('layouts.app')

@section('title', $masterConfig['label'])
@section('page-header', true)
@section('page-title', $masterConfig['label'])
@section('page-subtitle', 'Registry driven master · '.number_format($items->total()).' records')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('masters.index') }}">Masters</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $masterConfig['label'] }}</li>
@endsection

@section('page-actions')
    @canPermission('masters.manage')
        <button type="button" class="btn btn-primary btn-sm" id="master-create-toggle">
            <i class="bi bi-plus-lg"></i> Add {{ $masterConfig['singular'] }}
        </button>
    @endcanPermission
@endsection

@section('content')
    <x-filter-panel :action="route('masters.show', $masterKey)" :reset-url="route('masters.show', $masterKey)">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label" for="q">Search</label>
                <x-search-box name="q" :value="$filters['q'] ?? null" placeholder="Search {{ strtolower($masterConfig['label']) }}" />
            </div>
            <div class="col-md-2">
                <label class="form-label" for="is_active">Status</label>
                <select class="form-select" id="is_active" name="is_active">
                    <option value="">All</option>
                    <option value="1" @selected(($filters['is_active'] ?? null) === '1')>Active</option>
                    <option value="0" @selected(($filters['is_active'] ?? null) === '0')>Inactive</option>
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
            <div class="col-md-2">
                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" value="1" id="trashed" name="trashed" @checked(request()->boolean('trashed'))>
                    <label class="form-check-label small" for="trashed">Show deleted</label>
                </div>
            </div>
        </div>
    </x-filter-panel>

    @canPermission('masters.manage')
        <div class="lp-card mb-3" id="master-create-panel" @if (! $errors->any()) hidden @endif>
            <div class="lp-card__body">
                <div class="fw-semibold mb-3"><i class="bi bi-plus-circle text-primary me-1"></i> New {{ $masterConfig['singular'] }}</div>
                <form method="POST" action="{{ route('masters.store', $masterKey) }}">
                    @csrf
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4"><x-input name="name" label="Name" required /></div>
                        @foreach ($masterConfig['columns'] as $column => $label)
                            @continue(in_array($column, ['name', 'slug', 'code'], true))
                            <div class="col-md-2"><x-input name="{{ $column }}" :label="$label" /></div>
                        @endforeach
                        @if (! empty($related['products']))
                            <div class="col-md-3">
                                <label class="form-label" for="new-product_id">Product</label>
                                <select class="form-select" id="new-product_id" name="product_id">
                                    @foreach ($related['products'] as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        @if (! empty($related['categories']))
                            <div class="col-md-3">
                                <label class="form-label" for="new-product_category_id">Parent category</label>
                                <select class="form-select" id="new-product_category_id" name="product_category_id">
                                    <option value="">None</option>
                                    @foreach ($related['categories'] as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        @if (! empty($related['departments']))
                            <div class="col-md-3">
                                <label class="form-label" for="new-department_id">Department</label>
                                <select class="form-select" id="new-department_id" name="department_id">
                                    @foreach ($related['departments'] as $department)
                                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-3">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" value="1" id="new_is_active" name="is_active" checked>
                                <label class="form-check-label" for="new_is_active">Active</label>
                            </div>
                        </div>
                        <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-check2"></i> Save</button></div>
                    </div>
                </form>
            </div>
        </div>
    @endcanPermission

    <x-card :padding="false">
        <div id="master-table-body">
            @include('masters.partials.table', ['items' => $items, 'masterKey' => $masterKey, 'masterConfig' => $masterConfig])
        </div>
    </x-card>
@endsection

@push('scripts')
<script type="module">
    LoanPro.bindTableFilters({ url: '{{ route('masters.show', $masterKey) }}', container: '#master-table-body' });

    document.getElementById('master-create-toggle')?.addEventListener('click', () => {
        const panel = document.getElementById('master-create-panel');
        panel.hidden = !panel.hidden;
        if (!panel.hidden) panel.querySelector('input[name="name"]')?.focus();
    });
</script>
@endpush
