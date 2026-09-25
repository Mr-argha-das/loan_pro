@props(['title' => null, 'icon' => null, 'description' => null])

<div {{ $attributes->merge(['class' => 'lp-form-section']) }}>
    @if ($title)
        <div class="lp-form-section__head">
            @if ($icon)<i class="bi {{ $icon }} text-primary"></i>@endif
            <div>
                <h6>{{ $title }}</h6>
                @if ($description)<div class="text-muted" style="font-size:.76rem">{{ $description }}</div>@endif
            </div>
        </div>
    @endif
    <div class="lp-form-section__body">{{ $slot }}</div>
</div>
