@extends('layouts.app')

@section('title', 'Insurance '.$application->application_code)
@section('page-header', true)
@section('page-title', 'Policy '.$application->application_code)
@section('page-subtitle', ($application->customer?->name ?? 'Customer').' · '.($application->category?->name ?? 'Insurance').' · '.($application->insurer?->name ?? 'Insurer pending'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('insurance.index') }}">Insurance</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $application->application_code }}</li>
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-lg-8">
            <x-card title="Policy summary" icon="bi-shield-check">
                <div class="row g-3 mb-3">
                    <div class="col-6 col-md-3"><x-stat-card label="Sum assured" :value="\App\Support\Format::compactInr($application->sum_assured)" icon="bi-shield-check" tone="primary" /></div>
                    <div class="col-6 col-md-3"><x-stat-card label="Premium" :value="\App\Support\Format::compactInr($application->premium_amount)" icon="bi-currency-rupee" tone="green" /></div>
                    <div class="col-6 col-md-3"><x-stat-card label="Frequency" :value="\App\Support\Format::titleCase($application->premium_frequency)" icon="bi-arrow-repeat" tone="blue" /></div>
                    <div class="col-6 col-md-3"><x-stat-card label="Term" :value="($application->policy_term_years ?? '—').' yrs'" icon="bi-calendar3" tone="orange" /></div>
                </div>

                <div class="row g-2 small">
                    <div class="col-6 col-md-4"><span class="text-muted">Policy number:</span> <span class="fw-semibold">{{ $application->policy_number ?? 'Pending' }}</span></div>
                    <div class="col-6 col-md-4"><span class="text-muted">Insurer:</span> <span class="fw-semibold">{{ $application->insurer?->name ?? '—' }}</span></div>
                    <div class="col-6 col-md-4"><span class="text-muted">Plan:</span> <span class="fw-semibold">{{ $application->plan?->name ?? '—' }}</span></div>
                    <div class="col-6 col-md-4"><span class="text-muted">Start date:</span> <span class="fw-semibold">{{ \App\Support\Format::date($application->policy_start_date) }}</span></div>
                    <div class="col-6 col-md-4"><span class="text-muted">End date:</span> <span class="fw-semibold">{{ \App\Support\Format::date($application->policy_end_date) }}</span></div>
                    <div class="col-6 col-md-4"><span class="text-muted">Status:</span> <x-status-badge :status="$application->status" /></div>
                    <div class="col-6 col-md-4"><span class="text-muted">Nominee:</span> <span class="fw-semibold">{{ $application->nominee_name ?? '—' }}{{ $application->nominee_relation ? ' ('.$application->nominee_relation.')' : '' }}</span></div>
                    <div class="col-6 col-md-4"><span class="text-muted">Commission:</span> <span class="fw-semibold">{{ \App\Support\Format::money($application->commission_amount) }}</span></div>
                    <div class="col-6 col-md-4"><span class="text-muted">Owner:</span> <span class="fw-semibold">{{ $application->assignee?->name ?? '—' }}</span></div>
                </div>

                @if ($application->notes)
                    <div class="lp-divider"></div>
                    <div class="fw-semibold mb-1">Notes</div>
                    <p class="text-muted small mb-0">{{ $application->notes }}</p>
                @endif
            </x-card>

            <x-card title="Documents" icon="bi-folder-check" class="mt-4">
                <div class="table-responsive">
                    <table class="lp-table">
                        <thead>
                            <tr>
                                <th scope="col">Document</th>
                                <th scope="col">Status</th>
                                <th scope="col">Uploaded</th>
                                <th scope="col" class="text-end">File</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($application->documents as $document)
                                <tr>
                                    <td class="fw-semibold">{{ $document->documentType?->name ?? 'Document' }}</td>
                                    <td><x-status-badge :status="$document->status" /></td>
                                    <td class="text-muted text-nowrap">{{ \App\Support\Format::date($document->created_at) }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('documents.preview', $document) }}" target="_blank" rel="noopener" class="btn btn-sm btn-light"><i class="bi bi-eye"></i></a>
                                        <a href="{{ route('documents.download', $document) }}" class="btn btn-sm btn-light"><i class="bi bi-download"></i></a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4"><x-empty-state icon="bi-folder2-open" title="No documents" message="Upload the proposal form and KYC documents." /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>

        <div class="col-lg-4">
            <x-card title="Status history" icon="bi-clock-history">
                <x-timeline :items="$application->statusHistories->sortBy('created_at')->map(fn ($history) => [
                    'title' => $history->status?->name ?? $history->stage,
                    'icon' => 'bi-check2',
                    'state' => 'completed',
                    'meta' => \App\Support\Format::dateTime($history->created_at),
                    'note' => $history->note,
                ])->values()->all()" />
            </x-card>

            @if ($application->lead)
                <x-card title="Source lead" icon="bi-funnel" class="mt-4">
                    <div class="lp-kv"><span class="lp-kv__label">Lead</span><span class="lp-kv__value"><a href="{{ route('leads.show', $application->lead) }}">{{ $application->lead->lead_code }}</a></span></div>
                    <div class="lp-kv"><span class="lp-kv__label">Owner</span><span class="lp-kv__value">{{ $application->lead->assignee?->name ?? '—' }}</span></div>
                </x-card>
            @endif
        </div>
    </div>
@endsection
