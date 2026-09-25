@props(['headers' => [], 'items' => null, 'empty' => 'No records found', 'emptyIcon' => 'bi-inbox'])

<div class="lp-table-wrap">
    <table {{ $attributes->merge(['class' => 'lp-table']) }}>
        <thead>
            <tr>
                @foreach ($headers as $key => $header)
                    @php
                        $isSortable = is_array($header) && ! empty($header['sortable']);
                        $column = is_array($header) ? ($header['key'] ?? $key) : $key;
                        $label = is_array($header) ? ($header['label'] ?? $key) : $header;
                        $currentSort = request('sort');
                        $direction = request('direction', 'asc');
                    @endphp
                    <th scope="col" @if ($isSortable) class="lp-table__sort" role="button" tabindex="0"
                        onclick="window.location='{{ request()->fullUrlWithQuery(['sort' => $column, 'direction' => $currentSort === $column && $direction === 'asc' ? 'desc' : 'asc']) }}'"
                        onkeydown="if(event.key==='Enter'){window.location=this.getAttribute('data-href')}" data-href="{{ request()->fullUrlWithQuery(['sort' => $column, 'direction' => $currentSort === $column && $direction === 'asc' ? 'desc' : 'asc']) }}" @endif>
                        {{ $label }}
                        @if ($isSortable)
                            <i class="bi bi-arrow-{{ $currentSort === $column ? ($direction === 'asc' ? 'up' : 'down') : 'down-up' }}"></i>
                        @endif
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @if (! empty($slot) ? trim($slot) !== '' : false)
                {{ $slot }}
            @elseif ($items && $items->count())
                {{ $slot }}
            @else
                <tr>
                    <td colspan="{{ count($headers) }}">
                        <x-empty-state :icon="$emptyIcon" :title="$empty" />
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
</div>

@if ($items instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $items->total() > 0)
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3 no-print">
        <div class="text-muted small">
            Showing <strong>{{ $items->firstItem() }}</strong> to <strong>{{ $items->lastItem() }}</strong> of <strong>{{ $items->total() }}</strong> records
        </div>
        <div class="d-flex align-items-center gap-3">
            <form method="GET" class="d-flex align-items-center gap-2 small">
                @foreach (request()->except('per_page', 'page') as $key => $value)
                    @if (is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                @endforeach
                <label for="per_page" class="text-muted mb-0">Rows</label>
                <select name="per_page" id="per_page" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                    @foreach (config('loanpro.pagination.options') as $option)
                        <option value="{{ $option }}" @selected((int) request('per_page', 25) === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </form>
            {{ $items->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endif
