<x-section title="Loan & Insurance Requirement" icon="bi-cash-coin" description="Product, category and purpose the customer needs.">
    <div class="row g-3">
        <div class="col-md-4">
            <x-select name="product_id" label="Product" required :options="$products" :value="$lead?->product_id" placeholder="Select product" data-product-select />
        </div>
        <div class="col-md-4">
            <label class="form-label" for="product_category_id">Category<span class="req" aria-hidden="true">*</span></label>
            <select class="form-select" id="product_category_id" name="product_category_id" required data-category-select>
                <option value="">Select category</option>
                @foreach ($allCategories as $category)
                    <option value="{{ $category->id }}" data-product="{{ $category->product_id }}" @selected(($lead?->product_category_id ?? old('product_category_id')) == $category->id)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="product_subcategory_id">Purpose / Sub category</label>
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
