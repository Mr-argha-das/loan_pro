@extends('layouts.app')

@section('title', 'Master Management')
@section('page-header', true)
@section('page-title', 'Master Management')
@section('page-subtitle', 'All reference data used across the platform — extend it without any frontend change.')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Masters</li>
@endsection

@section('content')
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3"><x-stat-card label="Master tables" :value="number_format($stats['masters'])" icon="bi-diagram-3" tone="primary" /></div>
        <div class="col-6 col-lg-3"><x-stat-card label="Total records" :value="number_format($stats['records'])" icon="bi-collection" tone="blue" /></div>
        <div class="col-6 col-lg-3"><x-stat-card label="Product catalogue" :value="number_format($masters->whereIn('key', ['product-categories', 'product-subcategories'])->sum('count'))" icon="bi-box-seam" tone="green" /></div>
        <div class="col-6 col-lg-3"><x-stat-card label="System masters" :value="number_format($masters->where('key', 'settings')->sum('count'))" icon="bi-sliders" tone="orange" /></div>
    </div>

    <div class="row g-3">
        @foreach ($masters as $master)
            <div class="col-md-6 col-xl-4">
                <a href="{{ route('masters.show', $master['key']) }}" class="lp-master-card text-decoration-none">
                    <span class="lp-master-card__icon"><i class="bi {{ $master['icon'] }}"></i></span>
                    <div class="flex-grow-1">
                        <div class="fw-semibold">{{ $master['label'] }}</div>
                        <div class="text-muted" style="font-size:.76rem">{{ $master['singular'] }} · {{ number_format($master['count']) }} records</div>
                    </div>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>
            </div>
        @endforeach
    </div>
@endsection
