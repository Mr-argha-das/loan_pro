@props([
    'name',
    'label' => null,
    'options' => [],
    'value' => null,
    'required' => false,
    'placeholder' => 'Select an option',
    'help' => null,
    'selectedKey' => 'id',
    'labelKey' => 'name',
])

@php
    $current = old($name, $value);
@endphp

<div class="mb-3">
    @if ($label)
        <label for="{{ $name }}" class="form-label">
            {{ $label }}@if ($required)<span class="req" aria-hidden="true">*</span>@endif
        </label>
    @endif

    <select
        id="{{ $name }}"
        name="{{ $name }}"
        @if ($required) required aria-required="true" @endif
        {{ $attributes->merge(['class' => 'form-select'.($errors->has($name) ? ' is-invalid' : '')]) }}
    >
        <option value="">{{ $placeholder }}</option>
        @php
            // A sequential list (['Active', 'Inactive']) submits its labels; a keyed map
            // ([1 => 'High'], ['new' => 'New']) submits its keys.
            $optionsAreList = is_array($options) && array_is_list($options);
        @endphp
        @foreach ($options as $key => $option)
            @php
                $optionValue = is_object($option)
                    ? data_get($option, $selectedKey)
                    : ($optionsAreList ? $option : $key);
                $optionLabel = is_object($option) ? data_get($option, $labelKey) : $option;
            @endphp
            <option value="{{ $optionValue }}" @selected((string) $current === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
        {{ $slot ?? '' }}
    </select>

    @error($name)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    @if ($help)<div class="form-text">{{ $help }}</div>@endif
</div>
