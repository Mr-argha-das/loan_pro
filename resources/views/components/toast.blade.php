@props(['message', 'type' => 'success', 'title' => null])

@php
    $icons = ['success' => 'bi-check-circle-fill', 'danger' => 'bi-exclamation-octagon-fill', 'warning' => 'bi-exclamation-triangle-fill', 'info' => 'bi-info-circle-fill'];
@endphp

<div {{ $attributes->merge(['class' => "lp-toast lp-toast--{$type}"]) }} role="status">
    <i class="bi {{ $icons[$type] ?? $icons['info'] }}"></i>
    <div class="flex-grow-1">
        @if ($title)<div class="fw-semibold">{{ $title }}</div>@endif
        <div class="text-muted small">{{ $message }}</div>
    </div>
</div>
