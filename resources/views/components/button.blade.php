@props([
    'variant' => 'primary',
    'size' => null,
    'icon' => null,
    'href' => null,
    'type' => 'button',
    'confirm' => null,
    'loading' => true,
])

@php
    $classes = trim(sprintf('btn btn-%s %s %s', $variant, $size ? 'btn-'.$size : '', $attributes->get('class') ?? ''));
    $tag = $href ? 'a' : 'button';
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @else type="{{ $type }}" @endif
    {{ $attributes->except('class')->merge(['class' => $classes]) }}
    @if ($confirm) data-confirm="{{ $confirm }}" @endif
    @if ($loading && ! $href) data-loading-button @endif
>
    @if ($icon)<i class="bi {{ $icon }}"></i>@endif
    {{ $slot }}
</{{ $tag }}>
