{{-- Renders a master's full field list (config 'fields') as form inputs.
     Expects: $masterConfig, $item (record or null), $idPrefix (unique id prefix). --}}
@foreach (($masterConfig['fields'] ?? []) as $name => $field)
    @php
        $value = old($name, $item ? data_get($item, $name) : null);
        $id = $idPrefix.'-'.$name;
        $type = $field['type'] ?? 'text';
    @endphp
    <div class="col-md-6">
        <label class="form-label" for="{{ $id }}">{{ $field['label'] }}</label>
        @if ($type === 'select')
            <select class="form-select" id="{{ $id }}" name="{{ $name }}">
                <option value="">— Select —</option>
                @foreach ($field['options'] as $optionValue => $optionLabel)
                    <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
                @endforeach
            </select>
        @elseif ($type === 'textarea')
            <textarea class="form-control" id="{{ $id }}" name="{{ $name }}" rows="2">{{ $value }}</textarea>
        @else
            <input class="form-control" id="{{ $id }}" name="{{ $name }}" type="{{ $type }}"
                   value="{{ $value }}" @if ($type === 'number') step="any" min="0" @endif>
        @endif
    </div>
@endforeach
