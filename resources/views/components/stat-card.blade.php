@props([
    'label',
    'value',
    'icon' => 'bi-graph-up',
    'tone' => 'primary',
    'meta' => null,
    'trend' => null,
    'href' => null,
])

@php
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => 'lp-stat d-block text-decoration-none text-body']) }}>
    <div class="d-flex align-items-start gap-3">
        <span class="lp-stat__icon lp-tone-{{ $tone }}"><i class="bi {{ $icon }}"></i></span>
        <div class="flex-grow-1">
            <div class="lp-stat__label">{{ $label }}</div>
            <div class="lp-stat__value">{{ $value }}</div>
            @if ($meta || $trend !== null)
                <div class="lp-stat__meta d-flex align-items-center gap-2">
                    @if ($trend !== null)
                        <span class="fw-semibold {{ $trend >= 0 ? 'text-success' : 'text-danger' }}">
                            <i class="bi {{ $trend >= 0 ? 'bi-arrow-up-right' : 'bi-arrow-down-right' }}"></i>{{ abs($trend) }}%
                        </span>
                    @endif
                    @if ($meta)<span>{{ $meta }}</span>@endif
                </div>
            @endif
        </div>
    </div>
</{{ $tag }}>
