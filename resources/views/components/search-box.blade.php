@props(['name' => 'q', 'value' => null, 'placeholder' => 'Search...', 'target' => null])

<div class="position-relative" style="max-width:320px">
    <i class="bi bi-search position-absolute" style="inset-inline-start:12px;top:50%;transform:translateY(-50%);color:var(--lp-muted)"></i>
    <label for="search-{{ $name }}" class="visually-hidden">{{ $placeholder }}</label>
    <input type="search" id="search-{{ $name }}" name="{{ $name }}" value="{{ $value }}"
           placeholder="{{ $placeholder }}" autocomplete="off"
           @if ($target) data-table-search="{{ $target }}" @endif
           {{ $attributes->merge(['class' => 'form-control ps-5']) }}>
</div>
