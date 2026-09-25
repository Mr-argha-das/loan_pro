@props(['label' => null, 'icon' => 'bi-three-dots-vertical', 'variant' => 'light', 'align' => 'end', 'size' => 'sm'])

<div class="dropdown">
    <button class="btn btn-{{ $variant }} {{ $size ? 'btn-'.$size : '' }} {{ $label ? '' : 'btn-icon' }}"
            data-bs-toggle="dropdown" aria-expanded="false" aria-haspopup="true">
        <i class="bi {{ $icon }}"></i>
        @if ($label)<span class="ms-1">{{ $label }}</span>@endif
    </button>
    <ul class="dropdown-menu dropdown-menu-{{ $align }}">
        {{ $slot }}
    </ul>
</div>
