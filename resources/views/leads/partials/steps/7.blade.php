<x-section title="Loan & Insurance Requirement" icon="bi-cash-coin" description="Amount, tenure and the product the customer needs.">
    <div class="row g-3">
        <div class="col-md-4">
            <x-select name="product_id" label="Product" required :options="$products" :value="$lead?->product_id" placeholder="Select product" />
        </div>
        <div class="col-md-4">
            <x-select name="product_category_id" label="Category" :options="$categories" :value="$lead?->product_category_id" placeholder="Select category" />
        </div>
        <div class="col-md-4">
            <label class="form-label" for="product_subcategory_id">Purpose / Plan</label>
            <select class="form-select" id="product_subcategory_id" name="product_subcategory_id">
                <option value="">Select purpose</option>
                @foreach ($subcategories as $subcategory)
                    <option value="{{ $subcategory->id }}" data-category="{{ $subcategory->product_category_id }}"
                            @selected(($lead?->product_subcategory_id ?? old('product_subcategory_id')) == $subcategory->id)>
                        {{ $subcategory->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <x-input name="loan_amount" label="Required amount" type="number" step="1000" required :value="$lead?->loan_amount" icon="bi-currency-rupee" />
        </div>
        <div class="col-md-3">
            <x-input name="tenure_months" label="Preferred tenure (months)" type="number" required :value="$lead?->tenure_months ?? 36" />
        </div>
        <div class="col-md-3">
            <x-input name="credit_score" label="Credit score" type="number" min="300" max="900" :value="$lead?->credit_score" />
        </div>
        <div class="col-md-3">
            <x-input name="preferred_bank" label="Preferred lender" :value="$lead?->preferred_bank" />
        </div>
    </div>

    <div class="alert alert-secondary small mt-3 mb-0 d-flex gap-2">
        <i class="bi bi-lightbulb"></i>
        <div>Lender eligibility, EMI and ROI are calculated from the live lender product master in the next step.</div>
    </div>
</x-section>

<div class="d-flex justify-content-between">
    <button type="button" class="btn btn-outline-secondary" data-wizard-back><i class="bi bi-arrow-left"></i> Back</button>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-light" data-save-draft><i class="bi bi-save"></i> Save as Draft</button>
        <button type="submit" class="btn btn-primary">Save &amp; Continue <i class="bi bi-arrow-right"></i></button>
    </div>
</div>
