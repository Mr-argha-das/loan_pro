@props(['lead'])

<x-status-badge :status="$lead->status" :label="$lead->leadStatus?->name ?? \App\Support\StatusBadge::label($lead->status)" />
