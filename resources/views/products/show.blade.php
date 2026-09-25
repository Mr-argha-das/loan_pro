@extends('layouts.app')

@php
    $theme = config('loanpro.product_themes.'.$product->slug, ['color' => 'primary', 'icon' => 'bi-box']);
    $tone = ['success' => 'green', 'warning' => 'orange', 'info' => 'blue', 'danger' => 'red'][$theme['color']] ?? 'primary';
@endphp

@section('title', $product->name)
@section('page-header', true)
@section('page-title', $product->name)
@section('page-subtitle', \App\Support\Format::titleCase($product->slug).' · '.$product->categories->count().' categories · '.$product->categories->sum(fn ($c) => $c->subcategories->count()).' purposes')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Products</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $product->name }}</li>
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-lg-7">
            <x-card title="{{ $product->name }} categories" icon="{{ $theme['icon'] }}" description="Categories and purposes are stored in the product master.">
                @include('products.partials.categories', ['categories' => $product->categories, 'product' => $product])
            </x-card>
        </div>

        <div class="col-lg-5">
            <x-card title="Product summary" icon="bi-info-circle">
                <div class="lp-kv"><span class="lp-kv__label">Slug</span><span class="lp-kv__value">{{ $product->slug }}</span></div>
                <div class="lp-kv"><span class="lp-kv__label">Code</span><span class="lp-kv__value">{{ $product->code ?? '—' }}</span></div>
                <div class="lp-kv"><span class="lp-kv__label">Status</span><span class="lp-kv__value"><x-status-badge :status="$product->is_active ? 'active' : 'inactive'" /></span></div>
                @if ($product->description)
                    <p class="text-muted small mt-2 mb-0">{{ $product->description }}</p>
                @endif
            </x-card>

            <x-card title="Lender products" icon="bi-bank" description="Offers mapped to this product line." class="mt-4">
                <div class="table-responsive">
                    <table class="lp-table">
                        <thead>
                            <tr>
                                <th scope="col">Lender</th>
                                <th scope="col">Product</th>
                                <th scope="col">ROI</th>
                                <th scope="col">Max amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($lenderProducts as $offer)
                                <tr>
                                    <td class="fw-semibold">{{ $offer->lender?->name }}</td>
                                    <td>{{ $offer->product_name }}</td>
                                    <td>{{ $offer->roi !== null ? $offer->roi.'%' : '—' }}</td>
                                    <td>{{ \App\Support\Format::compactInr($offer->max_amount) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4"><x-empty-state icon="bi-bank" title="No lender offers" message="Map lender products from Lender Management." /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>
    </div>
@endsection
