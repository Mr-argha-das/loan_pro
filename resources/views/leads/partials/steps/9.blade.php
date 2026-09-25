<x-section title="Selected Lenders" icon="bi-list-check"
           description="Fine-tune the amount, ROI and fees for every shortlisted lender before submission.">
    @if ($selectedLenders->isEmpty())
        <x-empty-state icon="bi-bank" title="No lenders selected yet" message="Go back to the lender selection step to shortlist products.">
            <x-slot:action>
                <a href="{{ route('leads.wizard', ['lead' => $lead, 'step' => 8]) }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-arrow-left"></i> Select lenders
                </a>
            </x-slot:action>
        </x-empty-state>
    @else
        @foreach ($selectedLenders as $index => $selected)
            <div class="lp-card mb-3">
                <div class="lp-card__header">
                    <div class="d-flex align-items-center gap-2">
                        <span class="lp-lender-card__logo">{{ $selected->lender?->code ?? '—' }}</span>
                        <div>
                            <div class="fw-semibold">{{ $selected->lender?->name }}</div>
                            <div class="text-muted" style="font-size:.75rem">{{ $selected->lenderProduct?->product_name }}</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        @if ($selected->is_primary)
                            <span class="lp-badge bg-success-subtle text-success bg-opacity-10"><i class="bi bi-star-fill"></i> Primary</span>
                        @endif
                        <button type="button" class="btn btn-sm btn-light text-danger" data-bs-toggle="collapse" data-bs-target="#lender-edit-{{ $selected->id }}">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                    </div>
                </div>

                <div class="lp-card__body">
                    <div class="row g-3">
                        <div class="col-6 col-md-2"><div class="lp-lender-metric">Loan amount<strong>{{ \App\Support\Format::money($selected->loan_amount) }}</strong></div></div>
                        <div class="col-6 col-md-2"><div class="lp-lender-metric">ROI<strong>{{ $selected->roi }}%</strong></div></div>
                        <div class="col-6 col-md-2"><div class="lp-lender-metric">APR<strong>{{ $selected->apr ?? '—' }}%</strong></div></div>
                        <div class="col-6 col-md-2"><div class="lp-lender-metric">EMI<strong>{{ \App\Support\Format::money($selected->emi) }}</strong></div></div>
                        <div class="col-6 col-md-2"><div class="lp-lender-metric">Tenure<strong>{{ $selected->tenure_months }} months</strong></div></div>
                        <div class="col-6 col-md-2"><div class="lp-lender-metric">Processing fee<strong>{{ \App\Support\Format::money($selected->processing_fee) }}</strong></div></div>
                    </div>

                    <div class="collapse mt-3" id="lender-edit-{{ $selected->id }}">
                        <div class="lp-divider my-2"></div>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label" for="amount-{{ $selected->id }}">Loan amount</label>
                                <input type="number" class="form-control" id="amount-{{ $selected->id }}"
                                       name="lenders[{{ $index }}][loan_amount]" value="{{ (float) $selected->loan_amount }}" step="1000">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" for="roi-{{ $selected->id }}">ROI %</label>
                                <input type="number" class="form-control" id="roi-{{ $selected->id }}"
                                       name="lenders[{{ $index }}][roi]" value="{{ $selected->roi }}" step="0.01">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" for="tenure-{{ $selected->id }}">Tenure (months)</label>
                                <input type="number" class="form-control" id="tenure-{{ $selected->id }}"
                                       name="lenders[{{ $index }}][tenure_months]" value="{{ $selected->tenure_months }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" for="fee-{{ $selected->id }}">Processing fee</label>
                                <input type="number" class="form-control" id="fee-{{ $selected->id }}"
                                       name="lenders[{{ $index }}][processing_fee]" value="{{ (float) $selected->processing_fee }}" step="100">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="penal-{{ $selected->id }}">Penal charge %</label>
                                <input type="number" class="form-control" id="penal-{{ $selected->id }}"
                                       name="lenders[{{ $index }}][penal_charge]" value="{{ $selected->penal_charge }}" step="0.01">
                            </div>
                        </div>

                        @if (! empty($selected->required_documents))
                            <div class="mt-3">
                                <div class="fw-semibold small mb-1">Required documents</div>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach ((array) $selected->required_documents as $document)
                                        <span class="lp-badge bg-primary-subtle text-primary bg-opacity-10">{{ $document }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <input type="hidden" name="lenders[{{ $index }}][lender_id]" value="{{ $selected->lender_id }}">
                        <input type="hidden" name="lenders[{{ $index }}][lender_product_id]" value="{{ $selected->lender_product_id }}">
                    </div>
                </div>
            </div>
        @endforeach
    @endif
</x-section>

<div class="d-flex justify-content-between">
    <button type="button" class="btn btn-outline-secondary" data-wizard-back><i class="bi bi-arrow-left"></i> Back</button>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-light" data-save-draft><i class="bi bi-save"></i> Save as Draft</button>
        <button type="submit" class="btn btn-primary">Save &amp; Continue <i class="bi bi-arrow-right"></i></button>
    </div>
</div>
