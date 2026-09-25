<x-section title="Lender Selection" icon="bi-bank" description="Compare partner lenders and pick the products to submit.">
    @if (! $lead?->is_otp_verified)
        <div class="alert alert-warning d-flex gap-2 small">
            <i class="bi bi-exclamation-triangle"></i>
            <div>Complete OTP verification before selecting lenders.</div>
        </div>
    @endif

    <div class="row g-2 mb-3">
        <div class="col-md-3">
            <label class="form-label" for="lender-filter-search">Search lender</label>
            <input type="search" class="form-control" id="lender-filter-search" data-lender-filter placeholder="Bank or product name">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="lender-filter-type">Loan type</label>
            <select class="form-select" id="lender-filter-type" data-lender-filter>
                <option value="">All types</option>
                <option value="secured">Secured</option>
                <option value="unsecured">Unsecured</option>
                <option value="insurance">Insurance</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="lender-filter-category">Category</label>
            <select class="form-select" id="lender-filter-category" data-lender-filter>
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected($lead?->product_category_id == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="lender-filter-sort">Sort by</label>
            <select class="form-select" id="lender-filter-sort" data-lender-filter>
                <option value="roi">ROI</option>
                <option value="emi">EMI</option>
                <option value="amount">Max amount</option>
                <option value="tenure">Max tenure</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="lender-filter-direction">Order</label>
            <select class="form-select" id="lender-filter-direction" data-lender-filter>
                <option value="asc">Ascending</option>
                <option value="desc">Descending</option>
            </select>
        </div>
        <div class="col-md-1 d-flex align-items-end">
            <span class="lp-badge bg-primary-subtle text-primary bg-opacity-10 w-100 justify-content-center" id="lender-count" title="Lenders matching your filters">0</span>
        </div>
        <div class="col-md-1 d-flex align-items-end">
            <span class="lp-badge bg-success-subtle text-success bg-opacity-10 w-100 justify-content-center" title="Shortlisted so far"><i class="bi bi-check2"></i> <span id="lender-selected-count">{{ $selectedLenders?->count() ?? 0 }}</span></span>
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
