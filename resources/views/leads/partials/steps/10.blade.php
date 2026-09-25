@php
    $customer = $lead->customer;
    $primary = $selectedLenders->firstWhere('is_primary', true) ?? $selectedLenders->first();
@endphp

<x-section title="Lead Summary" icon="bi-clipboard-check" description="Review everything captured before creating the application.">
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="fw-semibold mb-3"><i class="bi bi-person me-1 text-primary"></i> Customer</div>
            <div class="d-flex align-items-center gap-3 mb-3">
                <span class="lp-avatar lp-avatar--lg">{{ $customer?->initials() }}</span>
                <div>
                    <div class="fw-semibold">{{ $customer?->name }}</div>
                    <div class="text-muted small">{{ $customer?->mobile }} &middot; {{ $customer?->email ?? 'No email' }}</div>
                    <div class="d-flex gap-1 mt-1">
                        <x-status-badge :status="$customer?->kyc_status ?? 'pending' :label="'KYC: '.ucfirst(str_replace('_', ' ', $customer?->kyc_status ?? 'pending'))" />
                        @if ($lead->is_otp_verified)
                            <span class="lp-badge bg-success-subtle text-success bg-opacity-10"><i class="bi bi-patch-check"></i> Verified</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="lp-kv"><span class="lp-kv__label">Lead ID</span><span class="lp-kv__value">{{ $lead->lead_code }}</span></div>
            <div class="lp-kv"><span class="lp-kv__label">Date of birth</span><span class="lp-kv__value">{{ $customer?->date_of_birth?->format('d M Y') ?? '—' }}</span></div>
            <div class="lp-kv"><span class="lp-kv__label">PAN</span><span class="lp-kv__value">{{ $customer?->pan_number ?? '—' }}</span></div>
            <div class="lp-kv"><span class="lp-kv__label">Aadhaar</span><span class="lp-kv__value">{{ $customer?->aadhaar_number ? 'XXXX XXXX '.substr($customer->aadhaar_number, -4) : '—' }}</span></div>
            <div class="lp-kv"><span class="lp-kv__label">Address</span><span class="lp-kv__value">{{ collect([$customer?->address, $customer?->city, $customer?->pincode])->filter()->implode(', ') ?: '—' }}</span></div>
        </div>

        <div class="col-lg-6">
            <div class="fw-semibold mb-3"><i class="bi bi-briefcase me-1 text-primary"></i> Employment &amp; income</div>
            <div class="lp-kv"><span class="lp-kv__label">Employment type</span><span class="lp-kv__value">{{ $customer?->employmentType?->name ?? '—' }}</span></div>
            <div class="lp-kv"><span class="lp-kv__label">Employer</span><span class="lp-kv__value">{{ $customer?->company_name ?? '—' }}</span></div>
            <div class="lp-kv"><span class="lp-kv__label">Designation</span><span class="lp-kv__value">{{ $customer?->designation ?? '—' }}</span></div>
            <div class="lp-kv"><span class="lp-kv__label">Monthly income</span><span class="lp-kv__value">{{ \App\Support\Format::money($customer?->monthly_income) }}</span></div>
            <div class="lp-kv"><span class="lp-kv__label">Existing EMI</span><span class="lp-kv__value">{{ \App\Support\Format::money($lead->existing_emi) }}</span></div>
            <div class="lp-kv"><span class="lp-kv__label">Credit score</span><span class="lp-kv__value">{{ $lead->credit_score ?? '—' }}</span></div>
        </div>

        <div class="col-lg-6">
            <div class="fw-semibold mb-3"><i class="bi bi-cash-coin me-1 text-primary"></i> Requirement</div>
            <div class="lp-kv"><span class="lp-kv__label">Product</span><span class="lp-kv__value">{{ $lead->product?->name ?? '—' }}</span></div>
            <div class="lp-kv"><span class="lp-kv__label">Category</span><span class="lp-kv__value">{{ $lead->category?->name ?? '—' }}</span></div>
            <div class="lp-kv"><span class="lp-kv__label">Purpose</span><span class="lp-kv__value">{{ $lead->subcategory?->name ?? '—' }}</span></div>
            <div class="lp-kv"><span class="lp-kv__label">Amount</span><span class="lp-kv__value">{{ \App\Support\Format::money($lead->loan_amount) }}</span></div>
            <div class="lp-kv"><span class="lp-kv__label">Tenure</span><span class="lp-kv__value">{{ \App\Support\Format::tenure($lead->tenure_months) }}</span></div>
            <div class="lp-kv"><span class="lp-kv__label">Indicative EMI</span><span class="lp-kv__value">{{ $primary?->emi ? \App\Support\Format::money($primary->emi) : '—' }}</span></div>
        </div>

        <div class="col-lg-6">
            <div class="fw-semibold mb-3"><i class="bi bi-bank me-1 text-primary"></i> Shortlisted lenders ({{ $selectedLenders->count() }})</div>
            @forelse ($selectedLenders as $lender)
                <div class="d-flex align-items-center gap-2 py-2 border-bottom">
                    <span class="lp-lender-card__logo" style="width:34px;height:34px;font-size:.7rem">{{ $lender->lender?->code }}</span>
                    <div class="flex-grow-1">
                        <div class="fw-semibold" style="font-size:.85rem">{{ $lender->lender?->name }}</div>
                        <div class="text-muted" style="font-size:.73rem">
                            ROI {{ $lender->roi }}% &middot; EMI {{ \App\Support\Format::money($lender->emi) }} &middot; Fee {{ \App\Support\Format::money($lender->processing_fee) }}
                        </div>
                    </div>
                    @if ($lender->is_primary)<span class="lp-badge bg-success-subtle text-success bg-opacity-10">Primary</span>@endif
                </div>
            @empty
                <div class="text-muted small">No lenders shortlisted. You can still create the application and add lenders later.</div>
            @endforelse

            <div class="mt-3">
                <div class="fw-semibold mb-2"><i class="bi bi-folder-check me-1 text-primary"></i> Documents</div>
                <div class="d-flex flex-wrap gap-1">
                    @forelse ($lead->documents as $document)
                        <span class="lp-badge bg-{{ $document->status === 'verified' ? 'success' : 'secondary' }}-subtle bg-opacity-10 text-{{ $document->status === 'verified' ? 'success' : 'secondary' }}">
                            {{ $document->documentType?->name }}
                        </span>
                    @empty
                        <span class="text-muted small">No documents uploaded.</span>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-12">
            <x-textarea name="notes" label="Internal remarks" rows="3" :value="$lead->notes"
                        placeholder="Anything the credit team should know about this lead." />
        </div>
    </div>
</x-section>

<div class="d-flex flex-wrap justify-content-between gap-2">
    <button type="button" class="btn btn-outline-secondary" data-wizard-back><i class="bi bi-arrow-left"></i> Back</button>
    <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-light" data-save-draft><i class="bi bi-save"></i> Save as Draft</button>
        <button type="submit" class="btn btn-success"><i class="bi bi-check2-circle"></i> Save Lead</button>
        @can('convert', $lead)
            <button type="submit" class="btn btn-primary" formaction="{{ route('leads.convert', $lead) }}" formmethod="POST">
                <i class="bi bi-arrow-right-circle"></i> Save &amp; Create Application
            </button>
        @endcan
    </div>
</div>
