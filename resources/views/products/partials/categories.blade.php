<div class="accordion lp-accordion" id="product-categories-{{ $product->id }}">
    @forelse ($categories as $index => $category)
        <div class="accordion-item">
            <h3 class="accordion-header" id="cat-head-{{ $product->id }}-{{ $category->id }}">
                <button class="accordion-button {{ $index === 0 ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse"
                        data-bs-target="#cat-body-{{ $product->id }}-{{ $category->id }}"
                        aria-expanded="{{ $index === 0 ? 'true' : 'false' }}"
                        aria-controls="cat-body-{{ $product->id }}-{{ $category->id }}">
                    <span class="fw-semibold">{{ $category->name }}</span>
                    <span class="lp-badge bg-primary-subtle text-primary bg-opacity-10 ms-2">{{ $category->subcategories->count() }}</span>
                </button>
            </h3>
            <div id="cat-body-{{ $product->id }}-{{ $category->id }}"
                 class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}"
                 aria-labelledby="cat-head-{{ $product->id }}-{{ $category->id }}"
                 data-bs-parent="#product-categories-{{ $product->id }}">
                <div class="accordion-body">
                    @if ($category->description)
                        <p class="text-muted small">{{ $category->description }}</p>
                    @endif

                    <div class="row g-2 mb-3">
                        <div class="col-6 col-md-3"><div class="lp-metric"><span>Ticket range</span><strong>{{ \App\Support\Format::compactInr($category->min_amount) }} – {{ \App\Support\Format::compactInr($category->max_amount) }}</strong></div></div>
                        <div class="col-6 col-md-3"><div class="lp-metric"><span>Tenure</span><strong>{{ $category->min_tenure_months ?? '—' }}–{{ $category->max_tenure_months ?? '—' }} mo</strong></div></div>
                        <div class="col-6 col-md-3"><div class="lp-metric"><span>Indicative ROI</span><strong>{{ $category->default_roi !== null ? $category->default_roi.'%' : '—' }}</strong></div></div>
                        <div class="col-6 col-md-3"><div class="lp-metric"><span>Purposes</span><strong>{{ $category->subcategories->count() }}</strong></div></div>
                    </div>

                    <div class="d-flex flex-wrap gap-1">
                        @forelse ($category->subcategories as $subcategory)
                            <span class="lp-badge bg-secondary-subtle text-secondary bg-opacity-10">{{ $subcategory->name }}</span>
                        @empty
                            <span class="text-muted small">No purposes configured yet.</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @empty
        <x-empty-state icon="bi-diagram-3" title="No categories yet" message="Add categories from Master Management to build this product." />
    @endforelse
</div>
