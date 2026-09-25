@props(['id', 'parent' => 'lp-accordion', 'title' => null, 'open' => false, 'badge' => null, 'icon' => null])

<div class="accordion-item">
    <h2 class="accordion-header" id="{{ $id }}-heading">
        <button class="accordion-button {{ $open ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse"
                data-bs-target="#{{ $id }}" aria-expanded="{{ $open ? 'true' : 'false' }}" aria-controls="{{ $id }}">
            @if ($icon)<i class="bi {{ $icon }} me-2"></i>@endif
            <span class="flex-grow-1">{{ $title }}</span>
            @if ($badge)<span class="lp-badge bg-primary-subtle text-primary bg-opacity-10 me-2">{{ $badge }}</span>@endif
        </button>
    </h2>
    <div id="{{ $id }}" class="accordion-collapse collapse {{ $open ? 'show' : '' }}"
         aria-labelledby="{{ $id }}-heading" data-bs-parent="#{{ $parent }}">
        <div class="accordion-body">{{ $slot }}</div>
    </div>
</div>
