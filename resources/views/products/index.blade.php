@extends('layouts.app')

@section('title', 'Product Master')
@section('page-header', true)
@section('page-title', 'Product Master')
@section('page-subtitle', 'Four product lines, their categories and purposes — all configured from the database.')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Products</li>
@endsection

@section('page-actions')
    @canPermission('masters.manage')
        <a href="{{ route('masters.show', 'product-categories') }}" class="btn btn-light btn-sm"><i class="bi bi-sliders"></i> Manage Categories</a>
    @endcanPermission
@endsection

@section('content')
    <div class="row g-3 mb-4">
        @foreach ($products as $product)
            @php
                $theme = config('loanpro.product_themes.'.$product->slug, ['color' => 'primary', 'icon' => 'bi-box']);
                $tone = ['success' => 'green', 'warning' => 'orange', 'info' => 'blue', 'danger' => 'red'][$theme['color']] ?? 'primary';
            @endphp
            <div class="col-md-6 col-xl-3">
                <a href="{{ route('products.show', $product) }}" class="lp-product-card lp-product-card--{{ $tone }} text-decoration-none">
                    <div class="lp-product-card__icon lp-tone-{{ $tone }}"><i class="bi {{ $theme['icon'] }}"></i></div>
                    <div class="lp-product-card__name">{{ $product->name }}</div>
                    <div class="lp-product-card__count">{{ $product->categories_count }} {{ \Illuminate\Support\Str::plural('category', $product->categories_count) }}</div>
                    <div class="lp-product-card__meta">
                        <span>{{ \App\Support\Format::titleCase($product->slug) }}</span>
                        <i class="bi bi-arrow-right"></i>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3"><x-stat-card label="Categories" :value="number_format($totals['categories'])" icon="bi-diagram-3" tone="primary" /></div>
        <div class="col-6 col-lg-3"><x-stat-card label="Sub-categories / plans" :value="number_format($totals['subcategories'])" icon="bi-list-nested" tone="blue" /></div>
        <div class="col-6 col-lg-3"><x-stat-card label="Partner lenders" :value="number_format($totals['lenders'])" icon="bi-bank" tone="green" /></div>
        <div class="col-6 col-lg-3"><x-stat-card label="Lender products" :value="number_format($totals['lender_products'])" icon="bi-box-seam" tone="orange" /></div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <x-card title="Category overview" icon="bi-diagram-3" description="Expand a product to see its categories and purposes.">
                @foreach ($products as $product)
                    @php
                        $theme = config('loanpro.product_themes.'.$product->slug, ['color' => 'primary', 'icon' => 'bi-box']);
                        $categories = App\Models\ProductCategory::query()->where('product_id', $product->id)->where('is_active', true)
                            ->ordered()->with(['subcategories' => fn ($q) => $q->where('is_active', true)->ordered()])->get();
                    @endphp

                    <div class="lp-cat-block mb-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="lp-stat__icon lp-tone-{{ ['success' => 'green', 'warning' => 'orange', 'info' => 'blue', 'danger' => 'red'][$theme['color']] ?? 'primary' }}" style="width:32px;height:32px;font-size:.95rem">
                                <i class="bi {{ $theme['icon'] }}"></i>
                            </span>
                            <div class="fw-semibold">{{ $product->name }}</div>
                            <span class="lp-badge bg-secondary-subtle text-secondary bg-opacity-10">{{ $categories->count() }} categories</span>
                        </div>

                        @include('products.partials.categories', ['categories' => $categories, 'product' => $product])
                    </div>
                @endforeach
            </x-card>
        </div>

        <div class="col-lg-5">
            <x-card title="Product performance" icon="bi-graph-up-arrow" description="Live counts from leads and applications.">
                @forelse ($summary as $row)
                    @php
                        $theme = config('loanpro.product_themes.'.$row['slug'], ['color' => 'primary', 'icon' => 'bi-box']);
                        $tone = ['success' => 'green', 'warning' => 'orange', 'info' => 'blue', 'danger' => 'red'][$theme['color']] ?? 'primary';
                    @endphp
                    <div class="d-flex align-items-center gap-3 py-2 border-bottom">
                        <span class="lp-stat__icon lp-tone-{{ $tone }}" style="width:34px;height:34px;font-size:1rem">
                            <i class="bi {{ $row['icon'] ?: $theme['icon'] }}"></i>
                        </span>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">
                                <a href="{{ route('products.show', $row['slug']) }}" class="text-decoration-none text-body">{{ $row['name'] }}</a>
                            </div>
                            <div class="text-muted" style="font-size:.75rem">
                                {{ $row['leads'] ?? 0 }} leads · {{ $row['applications'] ?? 0 }} applications
                            </div>
                        </div>
                        <div class="fw-semibold">{{ \App\Support\Format::compactInr($row['value'] ?? 0) }}</div>
                    </div>
                @empty
                    <x-empty-state icon="bi-graph-up" title="No product activity" message="Leads and applications will roll up here." />
                @endforelse
            </x-card>
        </div>
    </div>
@endsection
