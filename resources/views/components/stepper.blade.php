@props(['steps' => [], 'current' => 1, 'url' => null, 'maxReached' => 1])

<ol class="lp-stepper list-unstyled mb-0" aria-label="Lead creation steps">
    @foreach ($steps as $number => $label)
        @php
            $state = $number < $current ? 'is-done' : ($number === $current ? 'is-active' : '');
            $clickable = $url && $number <= $maxReached;
        @endphp
        <li class="lp-stepper__item {{ $state }}">
            @if ($clickable)
                <a href="{{ $url($number) }}" class="d-flex align-items-center gap-2 text-decoration-none text-reset">
            @else
                <span class="d-flex align-items-center gap-2">
            @endif
                <span class="lp-stepper__num">
                    @if ($number < $current)<i class="bi bi-check-lg"></i>@else{{ $number }}@endif
                </span>
                <span class="text-truncate">{{ $label }}</span>
            @if ($clickable)
                </a>
            @else
                </span>
            @endif
        </li>
    @endforeach
</ol>
