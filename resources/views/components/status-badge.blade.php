@props(['status', 'label' => null, 'dot' => true])

@php
    $color = \App\Support\StatusBadge::color($status);
@endphp

<span {{ $attributes->merge(['class' => "lp-badge lp-badge--{$color} bg-{$color}-subtle text-{$color} bg-opacity-10".($dot ? ' lp-badge--dot' : '')]) }}
      title="{{ \App\Support\StatusBadge::label($status) }}">
    {{ $label ?? \App\Support\StatusBadge::label($status) }}
</span>
