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
