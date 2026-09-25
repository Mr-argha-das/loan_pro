<div class="text-start">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <div class="fw-semibold">{{ $application->application_code }}</div>
            <div class="text-muted small">{{ $application->customer?->name }} &middot; {{ $application->customer?->mobile }}</div>
        </div>
        <x-status-badge :status="$application->status" :label="$application->applicationStatus?->name" />
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3"><div class="lp-metric"><span>Amount</span><strong>{{ \App\Support\Format::money($application->loan_amount) }}</strong></div></div>
        <div class="col-6 col-md-3"><div class="lp-metric"><span>EMI</span><strong>{{ \App\Support\Format::money($application->emi) }}</strong></div></div>
        <div class="col-6 col-md-3"><div class="lp-metric"><span>ROI</span><strong>{{ $application->roi !== null ? $application->roi.'%' : '—' }}</strong></div></div>
        <div class="col-6 col-md-3"><div class="lp-metric"><span>Tenure</span><strong>{{ \App\Support\Format::tenure($application->tenure_months) }}</strong></div></div>
    </div>

    <div class="lp-kv"><span class="lp-kv__label">Lender</span><span class="lp-kv__value">{{ $application->lender?->name ?? '—' }}</span></div>
    <div class="lp-kv"><span class="lp-kv__label">Category</span><span class="lp-kv__value">{{ $application->category?->name ?? '—' }}</span></div>
    <div class="lp-kv"><span class="lp-kv__label">Purpose</span><span class="lp-kv__value">{{ $application->subcategory?->name ?? '—' }}</span></div>
    <div class="lp-kv"><span class="lp-kv__label">Sanctioned</span><span class="lp-kv__value">{{ \App\Support\Format::money($application->sanctioned_amount) }}</span></div>
    <div class="lp-kv"><span class="lp-kv__label">Disbursed</span><span class="lp-kv__value">{{ \App\Support\Format::money($application->disbursed_amount) }}</span></div>
    <div class="lp-kv"><span class="lp-kv__label">Owner</span><span class="lp-kv__value">{{ $application->assignee?->name ?? '—' }}</span></div>

    <div class="d-grid mt-3">
        <a href="{{ route('applications.show', $application) }}" class="btn btn-primary"><i class="bi bi-box-arrow-up-right"></i> Open full application</a>
    </div>
</div>
