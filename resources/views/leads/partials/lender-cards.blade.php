@forelse ($lenderProducts as $index => $product)
    <div class="col-xl-4 col-md-6">
        <div class="lp-lender-card" data-lender-card data-lender-id="{{ $product['lender_id'] }}"
             data-lender-product-id="{{ $product['id'] }}" data-roi="{{ $product['roi'] }}"
             data-apr="{{ $product['apr'] }}" data-processing-fee="{{ $product['processing_fee'] }}"
             data-penal-charge="{{ $product['penal_charge'] }}">
            <div class="d-flex align-items-start gap-2 mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" data-lender-toggle
                           id="lender-{{ $product['id'] }}" aria-label="Select {{ $product['lender_name'] }}">
                </div>
                <span class="lp-lender-card__logo">{{ $product['lender_code'] ?? strtoupper(substr($product['lender_name'], 0, 2)) }}</span>
                <div class="flex-grow-1">
                    <div class="fw-semibold">{{ $product['lender_name'] }}</div>
                    <div class="text-muted" style="font-size:.73rem">{{ $product['product_name'] }}</div>
                    <span class="lp-badge bg-{{ $product['online_status'] === 'online' ? 'success' : 'secondary' }}-subtle bg-opacity-10 text-{{ $product['online_status'] === 'online' ? 'success' : 'secondary' }} mt-1">
                        <i class="bi bi-{{ $product['online_status'] === 'online' ? 'wifi' : 'building' }}"></i>
                        {{ ucfirst($product['online_status'] ?? 'offline') }}
                    </span>
                </div>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-6"><div class="lp-lender-metric">Loan upto<strong>{{ \App\Support\Format::compactInr($product['max_amount']) }}</strong></div></div>
                <div class="col-6"><div class="lp-lender-metric">Max tenor<strong>{{ $product['max_tenure_months'] }} months</strong></div></div>
                <div class="col-6"><div class="lp-lender-metric">ROI<strong>{{ $product['roi'] !== null ? $product['roi'].'%' : '—' }}</strong></div></div>
                <div class="col-6"><div class="lp-lender-metric">APR<strong>{{ $product['apr'] !== null ? $product['apr'].'%' : '—' }}</strong></div></div>
                <div class="col-6"><div class="lp-lender-metric">EMI (indicative)<strong>{{ $product['emi'] ? \App\Support\Format::money($product['emi']) : '—' }}</strong></div></div>
                <div class="col-6"><div class="lp-lender-metric">Penal charge<strong>{{ $product['penal_charge'] !== null ? $product['penal_charge'].'%' : '—' }}</strong></div></div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <span class="lp-badge bg-secondary-subtle text-secondary bg-opacity-10">{{ ucfirst($product['loan_type'] ?? 'general') }}</span>
                <button type="button" class="btn btn-sm btn-light ms-auto" data-bs-toggle="collapse" data-bs-target="#lender-details-{{ $product['id'] }}" aria-expanded="false">
                    <i class="bi bi-eye"></i> Details
                </button>
            </div>

            <div class="collapse mt-3" id="lender-details-{{ $product['id'] }}">
                <div class="lp-divider my-2"></div>
                <div class="fw-semibold small mb-1">Eligibility</div>
                <p class="text-muted" style="font-size:.76rem">{{ $product['eligibility'] ?? 'Contact the lender desk for eligibility.' }}</p>

                <div class="fw-semibold small mb-1">Required documents</div>
                <div class="d-flex flex-wrap gap-1">
                    @foreach ((array) $product['required_documents'] as $document)
                        <span class="lp-badge bg-primary-subtle text-primary bg-opacity-10">{{ $document }}</span>
                    @endforeach
                </div>

                <div class="lp-kv mt-2"><span class="lp-kv__label">Processing fee</span><span class="lp-kv__value">{{ \App\Support\Format::money($product['processing_fee']) }}</span></div>
            </div>

            <div data-lender-preview data-amount="{{ $amount }}" data-tenure="{{ $tenure }}" hidden></div>
        </div>
    </div>
@empty
    <div class="col-12">
        <x-empty-state icon="bi-bank" title="No lenders match these filters" message="Try relaxing the loan type or category filter." />
    </div>
@endforelse
