@props(['id', 'title' => null, 'size' => null, 'footer' => null, 'submitLabel' => 'Save', 'action' => null, 'method' => 'POST'])

<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered {{ $size ? 'modal-'.$size : '' }}">
        <div class="modal-content">
            <form method="POST" action="{{ $action ?? url()->current() }}" enctype="multipart/form-data"
                  @if ($attributes->get('ajax')) data-ajax @endif>
                @csrf
                @if (! in_array($method, ['POST', 'GET'], true)) @method($method) @endif

                <div class="modal-header">
                    <h5 class="modal-title" id="{{ $id }}-label">{{ $title }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">{{ $slot }}</div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
