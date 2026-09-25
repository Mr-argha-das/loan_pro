<div class="row g-3">
    <div class="col-md-6">
        <div class="d-flex align-items-center gap-3 mb-3">
            <span class="lp-avatar lp-avatar--lg">{{ $lead->customer?->initials() }}</span>
            <div>
                <div class="fw-semibold">{{ $lead->customer?->name }}</div>
                <div class="text-muted small">{{ $lead->customer?->mobile }} &middot; {{ $lead->customer?->email ?? 'No email' }}</div>
                <x-status-badge :status="$lead->status" :label="$lead->leadStatus?->name" class="mt-1" />
            </div>
        </div>

        <div class="lp-kv"><span class="lp-kv__label">Lead ID</span><span class="lp-kv__value">{{ $lead->lead_code }}</span></div>
        <div class="lp-kv"><span class="lp-kv__label">Product</span><span class="lp-kv__value">{{ $lead->product?->name }}</span></div>
        <div class="lp-kv"><span class="lp-kv__label">Category</span><span class="lp-kv__value">{{ $lead->category?->name ?? '—' }}</span></div>
        <div class="lp-kv"><span class="lp-kv__label">Purpose</span><span class="lp-kv__value">{{ $lead->subcategory?->name ?? '—' }}</span></div>
        <div class="lp-kv"><span class="lp-kv__label">Source</span><span class="lp-kv__value">{{ $lead->source?->name ?? '—' }}</span></div>
    </div>

    <div class="col-md-6">
        <div class="lp-kv"><span class="lp-kv__label">Loan amount</span><span class="lp-kv__value">{{ \App\Support\Format::money($lead->loan_amount) }}</span></div>
        <div class="lp-kv"><span class="lp-kv__label">Tenure</span><span class="lp-kv__value">{{ \App\Support\Format::tenure($lead->tenure_months) }}</span></div>
        <div class="lp-kv"><span class="lp-kv__label">Monthly income</span><span class="lp-kv__value">{{ \App\Support\Format::money($lead->monthly_income) }}</span></div>
        <div class="lp-kv"><span class="lp-kv__label">Credit score</span><span class="lp-kv__value">{{ $lead->credit_score ?? '—' }}</span></div>
        <div class="lp-kv"><span class="lp-kv__label">Owner</span><span class="lp-kv__value">{{ $lead->assignee?->name ?? 'Unassigned' }}</span></div>
        <div class="lp-kv"><span class="lp-kv__label">Created</span><span class="lp-kv__value">{{ $lead->created_at?->format('d M Y, h:i A') }}</span></div>
        <div class="lp-kv"><span class="lp-kv__label">Wizard progress</span><span class="lp-kv__value">{{ $lead->current_step }}/10 &middot; {{ $lead->stepName() }}</span></div>
    </div>

    @if ($lead->remarks->isNotEmpty())
        <div class="col-12">
            <div class="lp-divider"></div>
            <div class="fw-semibold mb-2">Latest remarks</div>
            @foreach ($lead->remarks->take(3) as $remark)
                <div class="d-flex gap-2 mb-2">
                    <span class="lp-avatar" style="width:28px;height:28px;font-size:.65rem">{{ $remark->creator?->initials() }}</span>
                    <div>
                        <div class="small">{{ $remark->body }}</div>
                        <div class="text-muted" style="font-size:.7rem">{{ $remark->creator?->name }} &middot; {{ $remark->created_at?->diffForHumans() }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
