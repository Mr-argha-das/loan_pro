@props(['lead'])

@php
    $histories = $lead->statusHistories->keyBy('stage');
    $stages = [
        'Lead Created' => 'bi-plus-circle',
        'Verified' => 'bi-patch-check',
        'Lender Selected' => 'bi-bank',
        'Application Created' => 'bi-clipboard-data',
        'Under Review' => 'bi-hourglass-split',
        'Approved' => 'bi-check-circle',
        'Disbursed' => 'bi-cash-stack',
    ];

    $items = [];

    foreach ($stages as $stage => $icon) {
        $history = $histories[$stage] ?? $histories->first(fn ($item) => str_contains($item->stage ?? '', $stage));

        $items[] = [
            'title' => $stage,
            'icon' => $icon,
            'state' => $history->state ?? 'upcoming',
            'meta' => $history?->created_at?->format('d M Y, h:i A'),
            'note' => $history?->note,
        ];
    }
@endphp

<x-timeline :items="$items" />
