@foreach (['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'info'] as $key => $type)
    @if (session($key))
        <span data-flash="{{ $type }}" data-message="{{ session($key) }}" hidden></span>
    @endif
@endforeach

@if ($errors->any())
    <span data-flash="danger" data-message="{{ $errors->first() }}" hidden></span>
@endif
