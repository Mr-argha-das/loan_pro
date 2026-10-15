<x-section title="Lender Selection" icon="bi-bank" description="Compare partner lenders and pick the products to submit.">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div class="small text-muted">
            <i class="bi bi-funnel"></i>
            Showing only the lenders whose income range matches this customer's monthly income
            <strong>{{ $lead?->customer?->monthly_income ? \App\Support\Format::money($lead->customer->monthly_income) : 'not captured' }}</strong>.
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="lp-badge bg-primary-subtle text-primary bg-opacity-10" title="Lenders matching this customer">
                <i class="bi bi-bank"></i> <span id="lender-count">0</span> matched
            </span>
            <span class="lp-badge bg-success-subtle text-success bg-opacity-10" title="Shortlisted so far">
                <i class="bi bi-check2"></i> <span id="lender-selected-count">{{ $selectedLenders?->count() ?? 0 }}</span> selected
            </span>
        </div>
    </div>

    <div class="row g-3" id="lender-results"></div>

    <div id="lender-hidden-inputs"></div>
</x-section>

<div class="d-flex justify-content-between">
    <button type="button" class="btn btn-outline-secondary" data-wizard-back><i class="bi bi-arrow-left"></i> Back</button>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-light" data-save-draft><i class="bi bi-save"></i> Save as Draft</button>
        <button type="submit" class="btn btn-primary">Save &amp; Continue <i class="bi bi-arrow-right"></i></button>
    </div>
</div>
