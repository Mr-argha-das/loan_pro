@props(['variant' => 'secondary', 'dot' => false, 'icon' => null])

<span {{ $attributes->merge(['class' => "lp-badge lp-badge--{$variant} bg-{$variant}-subtle text-{$variant} bg-opacity-10".($dot ? ' lp-badge--dot' : '')]) }}>
    @if ($icon)<i class="bi {{ $icon }}"></i>@endif
    {{ $slot }}
</span>
