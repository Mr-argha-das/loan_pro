@props([
    'name' => 'file',
    'label' => 'Upload document',
    'accept' => '.pdf,.jpg,.jpeg,.png',
    'required' => false,
    'help' => 'PDF, JPG, JPEG or PNG up to 5 MB',
    'existing' => null,
])

<div class="mb-3">
    <label class="form-label" for="{{ $name }}">{{ $label }}@if ($required)<span class="req">*</span>@endif</label>

    @if ($existing)
        <div class="lp-file-row">
            <span class="lp-file-row__icon"><i class="bi {{ $existing['is_image'] ? 'bi-file-earmark-image' : 'bi-file-earmark-pdf' }}"></i></span>
            <div class="flex-grow-1">
                <div class="fw-semibold small">{{ $existing['name'] }}</div>
                <div class="text-muted" style="font-size:.74rem">{{ $existing['size'] }} &middot; {{ $existing['status'] }}</div>
            </div>
            <a href="{{ $existing['url'] }}" class="btn btn-sm btn-light" target="_blank" rel="noopener"><i class="bi bi-eye"></i></a>
        </div>
    @endif

    <label class="lp-dropzone d-block" data-dropzone>
        <input type="file" id="{{ $name }}" name="{{ $name }}" accept="{{ $accept }}" class="visually-hidden"
               @if ($required) required @endif data-file-input>
        <i class="bi bi-cloud-arrow-up"></i>
        <div class="fw-semibold small mt-2">Click to browse or drop a file here</div>
        <div class="text-muted" style="font-size:.74rem">{{ $help }}</div>
        <div class="small text-primary mt-2 d-none" data-file-name></div>
    </label>

    @error($name)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
</div>
