@if ($items instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $items->total() > 0)
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3 no-print">
        <div class="text-muted small">
            Showing <strong>{{ $items->firstItem() }}</strong>–<strong>{{ $items->lastItem() }}</strong> of <strong>{{ $items->total() }}</strong> records
        </div>
        {{ $items->links('pagination::bootstrap-5') }}
    </div>
@endif
