@props(['title' => null, 'subtitle' => null, 'icon' => null, 'padding' => true, 'footer' => null])

<div {{ $attributes->merge(['class' => 'lp-card']) }}>
    @if ($title || isset($actions))
        <div class="lp-card__header">
            <div class="d-flex align-items-center gap-2">
                @if ($icon)
                    <span class="lp-tone-primary rounded-3 d-grid" style="width:34px;height:34px;place-items:center">
                        <i class="bi {{ $icon }}"></i>
                    </span>
                @endif
                <div>
                    <h2 class="lp-card__title">{{ $title }}</h2>
                    @if ($subtitle)<div class="text-muted" style="font-size:.78rem">{{ $subtitle }}</div>@endif
                </div>
            </div>
            @isset($actions)<div class="d-flex align-items-center gap-2">{{ $actions }}</div>@endisset
        </div>
    @endif

    <div class="{{ $padding ? 'lp-card__body' : '' }}">{{ $slot }}</div>

    @isset($footer)
        <div class="lp-card__footer">{{ $footer }}</div>
    @endisset
</div>
