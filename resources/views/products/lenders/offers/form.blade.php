@extends('layouts.app')

@php
    $isEdit = $offer->exists;
    $action = $isEdit
        ? route('lenders.offers.update', [$lender, $offer])
        : route('lenders.offers.store', $lender);
    $docs = old('required_documents', $isEdit ? implode("\n", (array) $offer->required_documents) : '');
@endphp

@section('title', $isEdit ? 'Edit offer' : 'Add offer')
@section('page-header', true)
@section('page-title', $isEdit ? 'Edit lender offer' : 'Add lender offer')
@section('page-subtitle', $lender->name.' · the income band decides which leads this offer is shown to')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('lenders.index') }}">Lenders</a></li>
    <li class="breadcrumb-item"><a href="{{ route('lenders.show', $lender) }}">{{ $lender->name }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $isEdit ? 'Edit offer' : 'Add offer' }}</li>
@endsection

@section('content')
    <form method="POST" action="{{ $action }}" novalidate>
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <div class="row g-4">
            <div class="col-lg-8">
                <x-section title="Product" icon="bi-box-seam" description="What this lender funds, and under which category.">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <x-select name="product_id" label="Product" required :options="$products" :value="old('product_id', $offer->product_id)" data-offer-product />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="product_category_id">Category</label>
                            <select class="form-select" id="product_category_id" name="product_category_id" data-offer-category>
                                <option value="">Any category</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" data-product="{{ $category->product_id }}" @selected((int) old('product_category_id', $offer->product_category_id) === $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="product_subcategory_id">Purpose / Sub-category</label>
                            <select class="form-select" id="product_subcategory_id" name="product_subcategory_id" data-offer-subcategory>
                                <option value="">Any purpose</option>
                                @foreach ($subcategories as $subcategory)
                                    <option value="{{ $subcategory->id }}" data-category="{{ $subcategory->product_category_id }}" @selected((int) old('product_subcategory_id', $offer->product_subcategory_id) === $subcategory->id)>{{ $subcategory->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6"><x-input name="product_name" label="Offer name" :value="old('product_name', $offer->product_name)" required placeholder="e.g. Personal Loan – Salaried" /></div>
                        <div class="col-md-4"><x-input name="code" label="Offer code" :value="old('code', $offer->code)" /></div>
                        <div class="col-md-4">
                            <label class="form-label" for="loan_type">Loan type<span class="req">*</span></label>
                            <select class="form-select" id="loan_type" name="loan_type" required>
                                @foreach (['unsecured' => 'Unsecured', 'secured' => 'Secured', 'insurance' => 'Insurance'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('loan_type', $offer->loan_type) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </x-section>

                <x-section title="Monthly income band" icon="bi-currency-rupee" description="Only leads whose monthly income falls inside this band are shown this offer. Leave a side empty for no limit.">
                    <div class="row g-3">
                        <div class="col-md-6"><x-input name="min_monthly_income" label="Minimum monthly income" type="number" step="500" min="0" :value="old('min_monthly_income', $offer->min_monthly_income)" icon="bi-currency-rupee" placeholder="e.g. 25000" /></div>
                        <div class="col-md-6"><x-input name="max_monthly_income" label="Maximum monthly income" type="number" step="500" min="0" :value="old('max_monthly_income', $offer->max_monthly_income)" icon="bi-currency-rupee" placeholder="e.g. 30000" help="Example: 25,000 to 30,000 is offered to customers earning in that range." /></div>
                    </div>
                </x-section>

                <x-section title="Amount, tenure and rates" icon="bi-percent" description="Used for EMI and processing fee calculations.">
                    <div class="row g-3">
                        <div class="col-md-6"><x-input name="min_amount" label="Minimum loan amount" type="number" step="1000" min="0" :value="old('min_amount', $offer->min_amount)" icon="bi-currency-rupee" /></div>
                        <div class="col-md-6"><x-input name="max_amount" label="Maximum loan amount" type="number" step="1000" min="0" :value="old('max_amount', $offer->max_amount)" icon="bi-currency-rupee" /></div>
                        <div class="col-md-6"><x-input name="min_tenure_months" label="Minimum tenure (months)" type="number" min="1" :value="old('min_tenure_months', $offer->min_tenure_months)" /></div>
                        <div class="col-md-6"><x-input name="max_tenure_months" label="Maximum tenure (months)" type="number" min="1" :value="old('max_tenure_months', $offer->max_tenure_months)" /></div>
                        <div class="col-md-6"><x-input name="roi" label="ROI %" type="number" step="0.001" min="0" :value="old('roi', $offer->roi)" /></div>
                        <div class="col-md-6"><x-input name="apr" label="APR %" type="number" step="0.001" min="0" :value="old('apr', $offer->apr)" /></div>
                        <div class="col-md-4"><x-input name="processing_fee" label="Processing fee" type="number" step="0.001" min="0" :value="old('processing_fee', $offer->processing_fee)" /></div>
                        <div class="col-md-2">
                            <label class="form-label" for="processing_fee_type">Unit</label>
                            <select class="form-select" id="processing_fee_type" name="processing_fee_type">
                                <option value="percent" @selected(old('processing_fee_type', $offer->processing_fee_type) === 'percent')>%</option>
                                <option value="fixed" @selected(old('processing_fee_type', $offer->processing_fee_type) === 'fixed')>₹ fixed</option>
                            </select>
                        </div>
                        <div class="col-md-4"><x-input name="penal_charge" label="Penal charge" type="number" step="0.001" min="0" :value="old('penal_charge', $offer->penal_charge)" /></div>
                        <div class="col-md-2">
                            <label class="form-label" for="penal_charge_type">Unit</label>
                            <select class="form-select" id="penal_charge_type" name="penal_charge_type">
                                <option value="percent" @selected(old('penal_charge_type', $offer->penal_charge_type) === 'percent')>%</option>
                                <option value="fixed" @selected(old('penal_charge_type', $offer->penal_charge_type) === 'fixed')>₹ fixed</option>
                            </select>
                        </div>
                    </div>
                </x-section>

                <x-section title="Eligibility and documents" icon="bi-clipboard-check">
                    <div class="row g-3">
                        <div class="col-md-6"><x-input name="min_credit_score" label="Minimum credit score" type="number" min="300" max="900" :value="old('min_credit_score', $offer->min_credit_score)" /></div>
                        <div class="col-md-6"><x-input name="sort_order" label="Display order" type="number" min="0" :value="old('sort_order', $offer->sort_order ?? 0)" /></div>
                        <div class="col-12">
                            <label class="form-label" for="eligibility">Eligibility notes</label>
                            <textarea class="form-control" id="eligibility" name="eligibility" rows="2">{{ old('eligibility', $offer->eligibility) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="required_documents">Required documents</label>
                            <textarea class="form-control" id="required_documents" name="required_documents" rows="3" placeholder="One document per line, e.g. Aadhaar card">{{ $docs }}</textarea>
                        </div>
                        <div class="col-12"><x-textarea name="remarks" label="Internal remarks" rows="2" :value="old('remarks', $offer->remarks)" /></div>
                    </div>
                </x-section>
            </div>

            <div class="col-lg-4">
                <x-card title="Availability" icon="bi-toggles">
                    <x-select name="status" label="Status" required :options="['active' => 'Active', 'inactive' => 'Inactive']" :value="old('status', $offer->status ?? 'active')" placeholder="" />
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1" @checked(old('is_featured', $offer->is_featured))>
                        <label class="form-check-label" for="is_featured">Featured offer</label>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('lenders.show', $lender) }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> {{ $isEdit ? 'Save changes' : 'Add offer' }}</button>
                    </div>
                </x-card>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    /* Narrow the category and sub-category lists to the chosen product. */
    (function () {
        const product = document.querySelector('[data-offer-product]');
        const category = document.querySelector('[data-offer-category]');
        const sub = document.querySelector('[data-offer-subcategory]');
        if (!product || !category || !sub) return;

        const filter = () => {
            const productId = product.value;
            [category, sub].forEach((select, index) => {
                const attr = index === 0 ? 'product' : 'category';
                const parentId = index === 0 ? productId : category.value;
                let keep = false;
                [...select.options].forEach((option) => {
                    if (!option.value) { option.hidden = false; return; }
                    const match = !parentId || option.dataset[attr] === parentId;
                    option.hidden = !match;
                    if (option.selected && !match) select.value = '';
                    if (option.selected && match) keep = true;
                });
                if (!keep) select.value = '';
            });
        };

        product.addEventListener('change', filter);
        category.addEventListener('change', filter);
        filter();
    })();
</script>
@endpush
