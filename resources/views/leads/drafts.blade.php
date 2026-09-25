@extends('layouts.app')

@section('title', 'Saved Drafts')
@section('page-header', true)
@section('page-title', 'Saved Drafts')
@section('page-subtitle', 'Resume incomplete lead onboarding where you left off.')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('leads.index') }}">Leads</a></li>
    <li class="breadcrumb-item active" aria-current="page">Drafts</li>
@endsection

@section('content')
    <div class="row g-3">
        @forelse ($drafts as $draft)
            <div class="col-md-6 col-xl-4">
                <div class="lp-card h-100">
                    <div class="lp-card__body">
                        <div class="d-flex align-items-start justify-content-between mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="lp-avatar">{{ $draft->customer?->initials() }}</span>
                                <div>
                                    <div class="fw-semibold">{{ $draft->customer?->name }}</div>
                                    <div class="text-muted" style="font-size:.74rem">{{ $draft->lead_code }}</div>
                                </div>
                            </div>
                            <x-status-badge :status="$draft->status" :label="$draft->leadStatus?->name" />
                        </div>

                        <div class="lp-kv"><span class="lp-kv__label">Category</span><span class="lp-kv__value">{{ $draft->category?->name ?? '—' }}</span></div>
                        <div class="lp-kv"><span class="lp-kv__label">Amount</span><span class="lp-kv__value">{{ \App\Support\Format::money($draft->loan_amount) }}</span></div>
                        <div class="lp-kv"><span class="lp-kv__label">Last step</span><span class="lp-kv__value">{{ $draft->current_step }}/10 &middot; {{ $draft->stepName() }}</span></div>
                        <div class="lp-kv"><span class="lp-kv__label">Last activity</span><span class="lp-kv__value">{{ $draft->last_activity_at?->diffForHumans() ?? '—' }}</span></div>

                        <div class="progress mt-3" role="progressbar" aria-valuenow="{{ $draft->progressPercent() }}" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar bg-warning" style="width: {{ $draft->progressPercent() }}%"></div>
                        </div>
                    </div>
                    <div class="lp-card__footer d-flex gap-2">
                        <a href="{{ route('leads.wizard', ['lead' => $draft, 'step' => $draft->current_step]) }}" class="btn btn-primary btn-sm flex-grow-1">
                            <i class="bi bi-play-fill"></i> Resume
                        </a>
                        <a href="{{ route('leads.show', $draft) }}" class="btn btn-light btn-sm"><i class="bi bi-eye"></i></a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <x-card>
                    <x-empty-state icon="bi-pencil-square" title="No saved drafts" message="Leads you save as draft will appear here so you can resume later.">
                        <x-slot:action>
                            @canPermission('leads.create')
                                <a href="{{ route('leads.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Create Lead</a>
                            @endcanPermission
                        </x-slot:action>
                    </x-empty-state>
                </x-card>
            </div>
        @endforelse
    </div>

    <x-pagination :paginator="$drafts" />
@endsection
