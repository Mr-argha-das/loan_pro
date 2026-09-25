@props(['icon' => 'bi-inbox', 'title' => 'Nothing here yet', 'message' => null])

<div class="lp-empty">
    <i class="bi {{ $icon }}"></i>
    <div class="fw-semibold text-body mb-1">{{ $title }}</div>
    @if ($message)<div class="small">{{ $message }}</div>@endif
    @isset($action)<div class="mt-3">{{ $action }}</div>@endisset
</div>
