@php
    use App\Support\AmountInWords;

    $money = fn ($value) => number_format((float) $value, 2);
    $items = $invoice->items->sortBy('sort_order')->values();
    $half = fn ($value) => number_format(round(((float) $value) / 2, 2), 2);
    $halfRate = fn ($rate) => rtrim(rtrim(number_format(((float) $rate) / 2, 3), '0'), '.');
    $customer = $invoice->customer;
    $customerAddress = collect([$customer?->address, $customer?->city, $customer?->state, $customer?->pincode])
        ->filter()->implode(', ');
    $gstin = $invoice->gstin ?: $customer?->gstin;
    $pan = $invoice->pan_number ?: $customer?->pan_number;
    $taxTotal = (float) $invoice->tax_amount;
    $taxRate = $items->max('tax_rate') ?: $invoice->tax_rate;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tax Invoice {{ $invoice->invoice_number }}</title>

<style>
    body { font-family: Arial, sans-serif; font-size: 12px; color: #000; }
    .invoice { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .invoice th, .invoice td { border: 1px solid #000; padding: 6px; vertical-align: middle; overflow-wrap: break-word; }
    .invoice th { text-align: center; }
    .center { text-align: center; }
    .right { text-align: right; }
    .bold { font-weight: bold; }
    .company-title { font-size: 17px; font-weight: bold; }
    .invoice-title { font-size: 15px; font-weight: bold; text-decoration: underline; }
    .amount-words { font-weight: bold; }
    .signature { height: 115px; vertical-align: bottom !important; }
    .muted { color: #444; }
</style>
</head>

<body>

<table class="invoice">

    <!-- Company Header -->
    <tr>
        <th colspan="8" class="center">
            <div class="company-title">{{ strtoupper($company['name']) }}</div>
            @if (filled($company['tagline'])) {{ $company['tagline'] }}<br> @endif
            @if (filled($company['address'])) {{ $company['address'] }}<br> @endif
            @if (filled($company['phone']) || filled($company['email']))
                {{ collect([$company['phone'] ? 'Phone: '.$company['phone'] : null, $company['email'] ? 'Email: '.$company['email'] : null])->filter()->implode('  |  ') }}<br>
            @endif
            <span class="invoice-title">TAX INVOICE</span>
            <br>
            <b>Original copy for recipient</b>
        </th>
    </tr>

    <!-- Customer and Invoice Details -->
    <tr>
        <td colspan="5" rowspan="2">
            <b>To,</b><br><br>

            <b>{{ $customer?->name ?? '—' }}</b>
            @if ($customerAddress !== '')
                <br>{{ $customerAddress }}
            @endif
            @if (filled($gstin))
                <br>GSTIN: {{ $gstin }}
            @endif
            @if (filled($pan))
                <br>PAN NO.: {{ $pan }}
            @endif
            @if (filled($customer?->mobile))
                <br>Mobile: {{ $customer->mobile }}
            @endif
        </td>

        <td colspan="3">
            <b>Invoice NO:</b> {{ $invoice->invoice_number }}
        </td>
    </tr>

    <tr>
        <td colspan="3">
            <b>DATE:</b> {{ $invoice->invoice_date?->format('d/m/Y') ?? '—' }}
            @if ($invoice->due_date)
                &nbsp;&nbsp; <b>DUE:</b> {{ $invoice->due_date->format('d/m/Y') }}
            @endif
            <br><br>
            <b>Product:</b> {{ $invoice->title ?: '—' }}
            @if ($invoice->place_of_supply)
                <br><b>Place of supply:</b> {{ $invoice->place_of_supply }}
            @endif
        </td>
    </tr>

    <!-- Items Heading -->
    <tr>
        <th>Sr.</th>
        <th colspan="2">Description</th>
        <th>HSN / SAC</th>
        <th>Qty</th>
        <th>Rate</th>
        <th>Tax %</th>
        <th>Amount</th>
    </tr>

    <!-- Items -->
    @forelse ($items as $item)
        <tr>
            <td class="center">{{ $loop->iteration }}</td>
            <td colspan="2">
                <b>{{ $item->description }}</b>
                @if ((float) $item->discount > 0)
                    <br><span class="muted">Discount: {{ $money($item->discount) }}</span>
                @endif
            </td>
            <td class="center">{{ $item->hsn_code ?: '—' }}</td>
            <td class="center">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }} {{ $item->unit }}</td>
            <td class="right">{{ $money($item->unit_price) }}</td>
            <td class="center">{{ rtrim(rtrim(number_format((float) $item->tax_rate, 2), '0'), '.') }}%</td>
            <td class="right">{{ $money($item->line_total) }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="8" class="center muted">No line items.</td>
        </tr>
    @endforelse

    <!-- Totals -->
    <tr>
        <td colspan="2" class="muted"></td>
        <td colspan="4" class="bold">Subtotal</td>
        <td class="right">{{ $money($invoice->subtotal) }}</td>
    </tr>

    @if ((float) $invoice->discount > 0)
        <tr>
            <td colspan="4" class="bold">Discount</td>
            <td class="right">- {{ $money($invoice->discount) }}</td>
        </tr>
    @endif

    @if ($taxTotal > 0)
        <tr>
            <td colspan="4" class="bold">ADD: CGST {{ $halfRate($taxRate) }}%</td>
            <td class="right">{{ $half($taxTotal) }}</td>
        </tr>
        <tr>
            <td colspan="4" class="bold">ADD: SGST {{ $halfRate($taxRate) }}%</td>
            <td class="right">{{ $half($taxTotal) }}</td>
        </tr>
    @endif

    <tr>
        <td colspan="2" class="muted">
            @if ((float) $invoice->paid_amount > 0)
                Paid: {{ $money($invoice->paid_amount) }}<br>
                Balance due: {{ $money($invoice->balance_amount) }}
            @endif
        </td>
        <td colspan="4" class="bold">GRAND TOTAL</td>
        <td class="right bold">{{ $money($invoice->total) }}</td>
    </tr>

    <!-- Amount in Words -->
    <tr>
        <td colspan="8" class="amount-words">
            Amount in Words: {{ AmountInWords::rupees($invoice->total) }}
        </td>
    </tr>

    <!-- Bank, registration details and signature -->
    <tr>
        <td colspan="5">
            @if (filled($company['bank_name']) || filled($company['bank_ifsc']))
                <b>Bank:</b> {{ $company['bank_name'] }}
                &nbsp;&nbsp;&nbsp;
                <b>IFSC CODE:</b> {{ $company['bank_ifsc'] }}<br>
            @endif
            @if (filled($company['bank_account']))
                <b>ACCOUNT NO.</b> {{ $company['bank_account'] }}<br>
            @endif
            @if (filled($company['gst']))
                <b>GSTIN NO.</b> {{ $company['gst'] }}<br>
            @endif
            @if (filled($company['pan']))
                <b>PAN:</b> {{ $company['pan'] }}<br>
            @endif
            @if (filled($company['sac']))
                <b>SAC CODE:</b> {{ $company['sac'] }}<br>
            @endif
            @if (filled($invoice->notes))
                <b>Notes:</b> {{ $invoice->notes }}<br>
            @endif
            @if (filled($invoice->terms))
                <b>Terms:</b> {{ $invoice->terms }}
            @endif
        </td>

        <td colspan="3" class="signature">
            <b>For: "{{ strtoupper($company['name']) }}"</b>
            <br><br>

            <b>Authorized Signatory</b>
            <br><br><br>

            __________________________
            <br>
            <b>Director</b>
        </td>
    </tr>

</table>

</body>
</html>
