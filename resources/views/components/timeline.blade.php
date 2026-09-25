@props(['items' => []])

<div class="lp-timeline">
    @foreach ($items as $item)
        @php
            $state = $item['state'] ?? 'upcoming';
            $class = match ($state) {
                'completed', 'done' => 'is-done',
                'current', 'active' => 'is-current',
                'rejected', 'lost' => 'is-rejected',
                default => '',
            };
        @endphp
        <div class="lp-timeline__item {{ $class }}">
            <span class="lp-timeline__dot">
                <i class="bi {{ $item['icon'] ?? ($class === 'is-done' ? 'bi-check' : 'bi-circle-fill') }}"></i>
            </span>
            <div class="lp-timeline__title">{{ $item['title'] }}</div>
            @if (! empty($item['meta']))
                <div class="lp-timeline__meta">{{ $item['meta'] }}</div>
            @endif
            @if (! empty($item['note']))
                <div class="text-muted small mt-1">{{ $item['note'] }}</div>
            @endif
        </div>
    @endforeach
</div>
