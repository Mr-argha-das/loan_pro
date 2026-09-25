@props(['id' => 'lp-accordion', 'flush' => false])

<div class="accordion lp-accordion {{ $flush ? 'accordion-flush' : '' }}" id="{{ $id }}">
    {{ $slot }}
</div>
