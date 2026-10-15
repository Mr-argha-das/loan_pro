@extends('layouts.app')

@php $isEdit = $invoice->exists; @endphp

@section('title', $isEdit ? 'Edit '.$invoice->invoice_number : 'Create Invoice')
@section('page-header', true)
@section('page-title', $isEdit ? 'Edit invoice '.$invoice->invoice_number : 'Create invoice')
@section('page-subtitle', 'Line items, taxes and payment terms — totals are recalculated automatically.')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('invoices.index') }}">Invoices</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $isEdit ? 'Edit' : 'Create' }}</li>
@endsection

@section('content')
    <form method="POST" action="{{ $isEdit ? route('invoices.update', $invoice) : route('invoices.store') }}" id="invoice-form" novalidate>
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <div class="row g-4">
            <div class="col-lg-8">
                <x-section title="Invoice details" icon="bi-receipt">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="customer_id">Customer<span class="req">*</span></label>
                            <select class="form-select" id="customer_id" name="customer_id" required>
                                <option value="">Select customer</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}" @selected((int) $invoice->customer_id === $customer->id)
                                            data-name="{{ $customer->name }}" data-mobile="{{ $customer->mobile }}"
                                            data-address="{{ collect([$customer->address, $customer->city, $customer->state, $customer->pincode])->filter()->implode(', ') }}"
                                            data-gst-treatment="{{ $customer->gst_treatment }}" data-gstin="{{ $customer->gstin }}"
                                            data-legal-name="{{ $customer->gst_legal_name }}" data-trade-name="{{ $customer->gst_trade_name }}"
                                            data-place-of-supply="{{ $customer->place_of_supply }}" data-pan="{{ $customer->pan_number }}">
                                        {{ $customer->name }} — {{ $customer->mobile }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6"><x-input name="title" label="Invoice title" :value="$invoice->title" required placeholder="e.g. Loan processing fee" /></div>

                        <div class="col-md-3"><x-input name="invoice_date" label="Invoice date" type="date" :value="$invoice->invoice_date?->toDateString() ?? now()->toDateString()" required /></div>
                        <div class="col-md-3"><x-input name="due_date" label="Due date" type="date" :value="$invoice->due_date?->toDateString()" required /></div>
                        <div class="col-md-3">
                            <label class="form-label" for="lead_id">Linked lead</label>
                            <select class="form-select" id="lead_id" name="lead_id">
                                <option value="">None</option>
                                @foreach ($leads as $lead)
                                    <option value="{{ $lead->id }}" @selected((int) $invoice->lead_id === $lead->id)>{{ $lead->lead_code }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="loan_application_id">Linked application</label>
                            <select class="form-select" id="loan_application_id" name="loan_application_id">
                                <option value="">None</option>
                                @foreach ($applications as $application)
                                    <option value="{{ $application->id }}" @selected((int) $invoice->loan_application_id === $application->id)>{{ $application->application_code }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </x-section>

                <x-section title="Customer & GST details" icon="bi-person-vcard"
                           description="Selecting a customer fills these in automatically. Edit them only if the invoice needs different details.">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label" for="bill_name">Customer name</label><input type="text" class="form-control" id="bill_name" readonly data-bill="name"></div>
                        <div class="col-md-6"><label class="form-label" for="bill_mobile">Mobile</label><input type="text" class="form-control" id="bill_mobile" readonly data-bill="mobile"></div>
                        <div class="col-12"><label class="form-label" for="bill_address">Address</label><input type="text" class="form-control" id="bill_address" readonly data-bill="address"></div>

                        <div class="col-md-4">
                            <label class="form-label" for="gst_treatment">GST treatment</label>
                            <select class="form-select" id="gst_treatment" name="gst_treatment" data-gst="gst_treatment">
                                <option value="">Select treatment</option>
                                @foreach (['Registered Business - Regular', 'Registered Business - Composition', 'Unregistered Business', 'Consumer', 'Overseas'] as $treatment)
                                    <option value="{{ $treatment }}" @selected(($invoice->gst_treatment ?? '') === $treatment)>{{ $treatment }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4"><x-input name="gstin" label="GSTIN / UIN" :value="$invoice->gstin" maxlength="15" data-gst="gstin" /></div>
                        <div class="col-md-4"><x-input name="pan_number" label="PAN" :value="$invoice->pan_number" maxlength="10" data-gst="pan" /></div>
                        <div class="col-md-6"><x-input name="gst_legal_name" label="Business legal name" :value="$invoice->gst_legal_name" data-gst="legal" /></div>
                        <div class="col-md-6"><x-input name="gst_trade_name" label="Business trade name" :value="$invoice->gst_trade_name" data-gst="trade" /></div>
                        <div class="col-md-6"><x-input name="place_of_supply" label="Place of supply" :value="$invoice->place_of_supply" data-gst="place" /></div>
                    </div>
                </x-section>

                <x-section title="Line items" icon="bi-list-ul" description="Add each billable charge; tax is applied per line.">
                    <div class="table-responsive">
                        <table class="lp-table" id="invoice-items-table">
                            <thead>
                                <tr>
                                    <th scope="col" style="min-width:220px">Description</th>
                                    <th scope="col" style="width:90px">Qty</th>
                                    <th scope="col" style="width:140px">Unit price</th>
                                    <th scope="col" style="width:100px">Tax %</th>
                                    <th scope="col" style="width:130px" class="text-end">Line total</th>
                                    <th scope="col" style="width:48px"></th>
                                </tr>
                            </thead>
                            <tbody id="invoice-items-body">
                                @php
                                    $rows = old('items', $isEdit && $invoice->items->isNotEmpty()
                                        ? $invoice->items->map(fn ($item) => [
                                            'description' => $item->description,
                                            'quantity' => $item->quantity,
                                            'unit_price' => $item->unit_price,
                                            'tax_rate' => $item->tax_rate,
                                        ])->all()
                                        : [['description' => '', 'quantity' => 1, 'unit_price' => '', 'tax_rate' => $invoice->tax_rate ?? 18]]);
                                @endphp

                                @foreach ($rows as $index => $row)
                                    <tr class="invoice-item-row">
                                        <td><input type="text" class="form-control" name="items[{{ $index }}][description]" value="{{ $row['description'] ?? '' }}" required placeholder="Charge description"></td>
                                        <td><input type="number" class="form-control" name="items[{{ $index }}][quantity]" value="{{ $row['quantity'] ?? 1 }}" step="0.01" min="0.01" data-line="quantity"></td>
                                        <td><input type="number" class="form-control" name="items[{{ $index }}][unit_price]" value="{{ $row['unit_price'] ?? '' }}" step="0.01" min="0" data-line="price"></td>
                                        <td><input type="number" class="form-control" name="items[{{ $index }}][tax_rate]" value="{{ $row['tax_rate'] ?? 18 }}" step="0.01" min="0" data-line="tax"></td>
                                        <td class="text-end fw-semibold" data-line-total>—</td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-light text-danger" data-remove-line aria-label="Remove line">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <button type="button" class="btn btn-light btn-sm mt-3" id="add-invoice-line"><i class="bi bi-plus-lg"></i> Add line item</button>
                </x-section>

                <x-section title="Notes &amp; terms" icon="bi-journal-text">
                    <div class="row g-3">
                        <div class="col-md-6"><x-textarea name="notes" label="Notes to customer" rows="3" :value="$invoice->notes" /></div>
                        <div class="col-md-6"><x-textarea name="terms" label="Terms &amp; conditions" rows="3" :value="$invoice->terms" /></div>
                    </div>
                </x-section>
            </div>

            <div class="col-lg-4">
                <x-section title="Summary" icon="bi-calculator">
                    <div class="row g-3">
                        <div class="col-6"><x-input name="discount" label="Discount" type="number" step="0.01" :value="$invoice->discount ?? 0" icon="bi-currency-rupee" /></div>
                        <div class="col-6"><x-input name="tax_rate" label="Default tax %" type="number" step="0.01" :value="$invoice->tax_rate ?? 18" /></div>
                    </div>

                    <div class="lp-divider"></div>

                    <div class="lp-kv"><span class="lp-kv__label">Subtotal</span><span class="lp-kv__value" id="summary-subtotal">—</span></div>
                    <div class="lp-kv"><span class="lp-kv__label">Tax</span><span class="lp-kv__value" id="summary-tax">—</span></div>
                    <div class="lp-kv"><span class="lp-kv__label">Discount</span><span class="lp-kv__value" id="summary-discount">—</span></div>
                    <div class="lp-kv fs-6"><span class="lp-kv__label fw-semibold">Total</span><span class="lp-kv__value text-primary" id="summary-total">—</span></div>

                    <div class="d-flex gap-2 mt-3">
                        <a href="{{ $isEdit ? route('invoices.show', $invoice) : route('invoices.index') }}" class="btn btn-outline-secondary flex-grow-1">Cancel</a>
                        <button class="btn btn-primary flex-grow-1"><i class="bi bi-check2"></i> {{ $isEdit ? 'Update' : 'Create invoice' }}</button>
                    </div>
                </x-section>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script type="module">
    const body = document.getElementById('invoice-items-body');
    const money = (value) => '₹' + Number(value || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    function recalc() {
        let subtotal = 0;
        let tax = 0;

        body.querySelectorAll('.invoice-item-row').forEach((row) => {
            const qty = parseFloat(row.querySelector('[data-line="quantity"]')?.value || 0);
            const price = parseFloat(row.querySelector('[data-line="price"]')?.value || 0);
            const rate = parseFloat(row.querySelector('[data-line="tax"]')?.value || 0);
            const line = qty * price;

            subtotal += line;
            tax += line * rate / 100;
            row.querySelector('[data-line-total]').textContent = money(line);
        });

        const discount = parseFloat(document.querySelector('[name="discount"]')?.value || 0);

        document.getElementById('summary-subtotal').textContent = money(subtotal);
        document.getElementById('summary-tax').textContent = money(tax);
        document.getElementById('summary-discount').textContent = money(discount);
        document.getElementById('summary-total').textContent = money(Math.max(0, subtotal + tax - discount));
    }

    function reindex() {
        body.querySelectorAll('.invoice-item-row').forEach((row, index) => {
            row.querySelectorAll('input').forEach((input) => {
                input.name = input.name.replace(/items\[\d+]/, `items[${index}]`);
            });
        });
    }

    document.getElementById('add-invoice-line')?.addEventListener('click', () => {
        const row = body.querySelector('.invoice-item-row').cloneNode(true);
        row.querySelectorAll('input').forEach((input) => { input.value = input.dataset.line === 'quantity' ? 1 : (input.dataset.line === 'tax' ? 18 : ''); });
        body.appendChild(row);
        reindex();
        recalc();
    });

    body.addEventListener('click', (event) => {
        const remove = event.target.closest('[data-remove-line]');
        if (!remove) return;
        if (body.querySelectorAll('.invoice-item-row').length === 1) {
            LoanPro.toast('At least one line item is required.', 'warning');
            return;
        }
        remove.closest('.invoice-item-row').remove();
        reindex();
        recalc();
    });

    body.addEventListener('input', recalc);
    document.querySelector('[name="discount"]')?.addEventListener('input', recalc);
    recalc();
</script>
@endpush

@push('scripts')
<script type="module">
    /* Customer selection fills the bill-to block and GST fields from the customer record. */
    const customerSelect = document.getElementById('customer_id');
    const field = (key) => document.querySelector(`[data-gst="${key}"]`);
    const bill = (key) => document.querySelector(`[data-bill="${key}"]`);

    customerSelect?.addEventListener('change', () => {
        const option = customerSelect.selectedOptions[0];
        if (!option || !option.value) return;

        const d = option.dataset;
        const gstin = (d.gstin || '').toUpperCase();

        bill('name').value = d.name ?? '';
        bill('mobile').value = d.mobile ?? '';
        bill('address').value = d.address ?? '';

        field('gst_treatment').value = d.gstTreatment || field('gst_treatment').value;
        field('gstin').value = gstin;
        // PAN is the 10 characters after the 2-digit state code in a GSTIN.
        field('pan').value = (d.pan || (gstin.length === 15 ? gstin.substring(2, 12) : '')).toUpperCase();
        field('legal').value = d.legalName ?? '';
        field('trade').value = d.tradeName ?? '';
        field('place').value = d.placeOfSupply ?? '';
    });
</script>
@endpush
