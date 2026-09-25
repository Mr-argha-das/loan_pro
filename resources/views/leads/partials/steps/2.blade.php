<x-section title="Basic Information" icon="bi-info-circle" description="Lead source, product interest and contact preferences.">
    <div class="row g-3">
        <div class="col-md-4">
            <x-select name="lead_source_id" label="Lead source" :options="$sources" :value="$lead?->lead_source_id" placeholder="How did this lead arrive?" />
        </div>
        <div class="col-md-4">
            <x-select name="lead_type" label="Lead type" :options="['fresh' => 'Fresh', 'existing' => 'Existing', 'priority' => 'Priority']"
                      :value="$lead?->lead_type ?? 'fresh'" placeholder="Select lead type" />
        </div>
        <div class="col-md-4">
            <x-select name="priority" label="Priority" :options="[1 => 'High', 2 => 'Medium', 3 => 'Low']"
                      :value="$lead?->priority ?? 2" placeholder="Select priority" />
        </div>

        <div class="col-md-4">
            <x-select name="product_id" label="Product" required :options="$products" :value="$lead?->product_id"
                      placeholder="Select product" help="Loans, Insurance, Cards or Real Estate — all database driven." />
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

        <div class="col-md-4">
            <x-select name="preferred_contact_method" label="Preferred contact"
                      :options="['call' => 'Phone call', 'sms' => 'SMS', 'whatsapp' => 'WhatsApp', 'email' => 'Email']"
                      :value="$lead?->preferred_contact_method ?? 'call'" placeholder="Select method" />
        </div>
        <div class="col-md-4">
            <x-select name="preferred_contact_time" label="Best time to call"
                      :options="['Morning (9-12)' => 'Morning (9 AM - 12 PM)', 'Afternoon (12-4)' => 'Afternoon (12 PM - 4 PM)', 'Evening (4-8)' => 'Evening (4 PM - 8 PM)']"
                      :value="$lead?->preferred_contact_time" placeholder="Select time slot" />
        </div>
        <div class="col-md-4">
            <x-input name="preferred_bank" label="Preferred lender" :value="$lead?->preferred_bank" placeholder="Any / specific bank" />
        </div>
    </div>
</x-section>

<div class="d-flex justify-content-between">
    <button type="button" class="btn btn-outline-secondary" data-wizard-back><i class="bi bi-arrow-left"></i> Back</button>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-light" data-save-draft><i class="bi bi-save"></i> Save as Draft</button>
        <button type="submit" class="btn btn-primary">Save &amp; Continue <i class="bi bi-arrow-right"></i></button>
    </div>
</div>
