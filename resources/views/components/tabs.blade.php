@props(['tabs' => [], 'active' => null, 'id' => 'lp-tabs'])

@php $active ??= array_key_first($tabs); @endphp

<ul class="nav lp-tabs" id="{{ $id }}" role="tablist">
    @foreach ($tabs as $key => $tab)
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $active === $key ? 'active' : '' }}" id="{{ $id }}-{{ $key }}-tab"
                    data-bs-toggle="tab" data-bs-target="#{{ $id }}-{{ $key }}" type="button" role="tab"
                    aria-controls="{{ $id }}-{{ $key }}" aria-selected="{{ $active === $key ? 'true' : 'false' }}">
                @if (! empty($tab['icon']))<i class="bi {{ $tab['icon'] }} me-1"></i>@endif
                {{ $tab['label'] ?? $key }}
                @if (! empty($tab['count']))
                    <span class="lp-badge bg-secondary-subtle text-secondary bg-opacity-10 ms-1">{{ $tab['count'] }}</span>
                @endif
            </button>
        </li>
    @endforeach
</ul>

{{ $slot }}
