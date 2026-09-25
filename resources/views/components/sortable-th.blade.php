@props([
    'column',
    'label',
    'sort' => request('sort'),
    'direction' => request('direction', 'desc'),
    'align' => null,
])

@php
    $isActive = $sort === $column;
    $next = $isActive && $direction === 'asc' ? 'desc' : 'asc';
    $icon = $isActive ? ($direction === 'asc' ? 'bi-sort-up' : 'bi-sort-down') : 'bi-arrow-down-up';
@endphp

<th scope="col" data-sort="{{ $column }}" @class(['lp-th-sort', 'is-active' => $isActive, 'text-end' => $align === 'end', 'text-center' => $align === 'center'])
    aria-sort="{{ $isActive ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none' }}"
    data-next-direction="{{ $next }}" tabindex="0" role="button">
    <span>{{ $label }}</span>
    <i class="bi {{ $icon }}" aria-hidden="true"></i>
</th>
