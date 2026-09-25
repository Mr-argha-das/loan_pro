@props(['variant' => 'info', 'title' => null, 'dismissible' => false, 'icon' => null])

@php
    $icons = ['success' => 'bi-check-circle', 'danger' => 'bi-exclamation-octagon', 'warning' => 'bi-exclamation-triangle', 'info' => 'bi-info-circle'];
@endphp

<div {{ $attributes->merge(['class' => "alert alert-{$variant}" . ($dismissible ? ' alert-dismissible fade show' : '')]) }} role="alert">
    <div class="d-flex gap-2">
        <i class="bi {{ $icon ?? $icons[$variant] ?? $icons['info'] }}"></i>
        <div class="flex-grow-1">
            @if ($title)<div class="fw-semibold mb-1">{{ $title }}</div>@endif
            {{ $slot }}
        </div>
    </div>
    @if ($dismissible)
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    @endif
</div>
