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

    // Logo: file ko public/images/logo.png me rakho (PNG/JPG)
    $logoPath = public_path('images/logo.png');
    $logoSrc = file_exists($logoPath)
        ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath))
        : null;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tax Invoice {{ $invoice->invoice_number }}</title>

<style>
    body { font-family: Arial, sans-serif; font-size: 12px; color: #111827; }
    .invoice { width: 100%; border-collapse: collapse; table-layout: fixed; border: 2px solid #0b2f6b; }
    .invoice th, .invoice td { border: 1px solid #9ca3af; padding: 6px; vertical-align: middle; overflow-wrap: break-word; }
    .center { text-align: center; }
    .right { text-align: right; }
    .bold { font-weight: bold; }
    .muted { color: #4b5563; }

    /* Header */
    .head { background: #ffffff; border-bottom: 3px solid #c9a227 !important; text-align: center; }
    .logo { height: 85px; }
    .company-title { font-size: 20px; font-weight: bold; color: #0b2f6b; }
    .head-info { font-size: 11px; color: #374151; line-height: 1.6; }
    .title-bar { background: #0b2f6b; color: #ffffff; text-align: center; padding: 7px; font-size: 15px; font-weight: bold; letter-spacing: 3px; }
    .title-bar span { color: #e6c35c; font-size: 10px; letter-spacing: 1px; font-weight: normal; }

    /* Customer + details */
    .to-box { background: #f1f5fb; }
    .meta-box { background: #fdf8e7; }
    .label { color: #0b2f6b; font-weight: bold; }

    /* Items */
    .th-row th { background: #c9a227; color: #ffffff; font-size: 11px; text-transform: uppercase; border-color: #a8851a; }
    .row-odd td { background: #ffffff; }
    .row-even td { background: #f6f8fc; }

    /* Totals */
    .t-label { background: #eef2f9; font-weight: bold; color: #0b2f6b; text-align: right; }
    .t-tax { background: #fdf8e7; font-weight: bold; color: #7a5c06; text-align: right; }
    .t-disc { background: #fef2f2; font-weight: bold; color: #b91c1c; text-align: right; }
    .grand { background: #0b2f6b; color: #e6c35c; font-weight: bold; font-size: 14px; }
    .grand-amt { background: #0b2f6b; color: #ffffff; font-weight: bold; font-size: 14px; text-align: right; }

    .amount-words { background: #fdf8e7; color: #5c4504; font-weight: bold; }
    .bank { background: #f1f5fb; line-height: 1.7; }
    .signature { height: 115px; vertical-align: bottom !important; text-align: center; background: #fdf8e7; }
    .sig-co { color: #0b2f6b; }
</style>
</head>

<body>

<table class="invoice">

    <!-- Company Header with Logo -->
    <tr>
        <td colspan="8" class="head">
            @if ($logoSrc)
                <img src="{{ $logoSrc }}" class="logo" alt="{{ $company['name'] }}"><br>
            @else
                <div class="company-title">{{ strtoupper($company['name']) }}</div>
            @endif
            <div class="head-info">
                @if (filled($company['tagline'])) {{ $company['tagline'] }}<br> @endif
                @if (filled($company['address'])) {{ $company['address'] }}<br> @endif
                @if (filled($company['phone']) || filled($company['email']))
                    {{ collect([$company['phone'] ? 'Phone: '.$company['phone'] : null, $company['email'] ? 'Email: '.$company['email'] : null])->filter()->implode('  |  ') }}
                @endif
            </div>
        </td>
    </tr>

    <!-- Title bar -->
    <tr>
        <td colspan="8" class="title-bar">
            TAX INVOICE<br>
            <span>Original copy for recipient</span>
        </td>
    </tr>

    <!-- Customer and Invoice Details -->
    <tr>
        <td colspan="5" rowspan="2" class="to-box" style="vertical-align: top;">
            <span class="label">To,</span><br><br>

            <b>{{ $customer?->name ?? '—' }}</b>
            @if ($customerAddress !== '')
                <br>{{ $customerAddress }}
            @endif
            @if (filled($gstin))
                <br><span class="label">GSTIN:</span> {{ $gstin }}
            @endif
            @if (filled($pan))
                <br><span class="label">PAN NO.:</span> {{ $pan }}
            @endif
            @if (filled($customer?->mobile))
                <br><span class="label">Mobile:</span> {{ $customer->mobile }}
            @endif
        </td>

        <td colspan="3" class="meta-box">
            <span class="label">Invoice NO:</span> {{ $invoice->invoice_number }}
        </td>
    </tr>

    <tr>
        <td colspan="3" class="meta-box">
            <span class="label">DATE:</span> {{ $invoice->invoice_date?->format('d/m/Y') ?? '—' }}
            @if ($invoice->due_date)
                &nbsp;&nbsp; <span class="label">DUE:</span> {{ $invoice->due_date->format('d/m/Y') }}
            @endif
            <br><br>
            <span class="label">Product:</span> {{ $invoice->title ?: '—' }}
            @if ($invoice->place_of_supply)
                <br><span class="label">Place of supply:</span> {{ $invoice->place_of_supply }}
            @endif
        </td>
    </tr>

    <!-- Items Heading -->
    <tr class="th-row">
        <th style="width: 6%;">Sr.</th>
        <th colspan="2" style="width: 30%;">Description</th>
        <th style="width: 12%;">HSN / SAC</th>
        <th style="width: 12%;">Qty</th>
        <th style="width: 14%;">Rate</th>
        <th style="width: 8%;">Tax %</th>
        <th style="width: 18%;">Amount</th>
    </tr>

    <!-- Items -->
    @forelse ($items as $item)
        <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
            <td class="center">{{ $loop->iteration }}</td>
            <td colspan="2">
                <b>{{ $item->description }}</b>
                @if ((float) $item->discount > 0)
                    <br><span style="color: #b91c1c;">Discount: {{ $money($item->discount) }}</span>
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
        <td colspan="7" class="t-label">Subtotal</td>
        <td class="right bold">{{ $money($invoice->subtotal) }}</td>
    </tr>

    @if ((float) $invoice->discount > 0)
        <tr>
            <td colspan="7" class="t-disc">Discount</td>
            <td class="right bold">- {{ $money($invoice->discount) }}</td>
        </tr>
    @endif

    @if ($taxTotal > 0)
        <tr>
            <td colspan="7" class="t-tax">ADD: CGST {{ $halfRate($taxRate) }}%</td>
            <td class="right bold">{{ $half($taxTotal) }}</td>
        </tr>
        <tr>
            <td colspan="7" class="t-tax">ADD: SGST {{ $halfRate($taxRate) }}%</td>
            <td class="right bold">{{ $half($taxTotal) }}</td>
        </tr>
    @endif

    <tr>
        <td colspan="3" class="muted">
            @if ((float) $invoice->paid_amount > 0)
                Paid: {{ $money($invoice->paid_amount) }}<br>
                Balance due: {{ $money($invoice->balance_amount) }}
            @endif
        </td>
        <td colspan="4" class="grand right">GRAND TOTAL</td>
        <td class="grand-amt">{{ $money($invoice->total) }}</td>
    </tr>

    <!-- Amount in Words -->
    <tr>
        <td colspan="8" class="amount-words">
            Amount in Words: {{ AmountInWords::rupees($invoice->total) }}
        </td>
    </tr>

    <!-- Bank, registration details and signature -->
    <tr>
        <td colspan="5" class="bank" style="vertical-align: top;">
            @if (filled($company['bank_name']) || filled($company['bank_ifsc']))
                <span class="label">Bank:</span> {{ $company['bank_name'] }}
                &nbsp;&nbsp;&nbsp;
                <span class="label">IFSC CODE:</span> {{ $company['bank_ifsc'] }}<br>
            @endif
            @if (filled($company['bank_account']))
                <span class="label">ACCOUNT NO.</span> {{ $company['bank_account'] }}<br>
            @endif
            @if (filled($company['gst']))
                <span class="label">GSTIN NO.</span> {{ $company['gst'] }}<br>
            @endif
            @if (filled($company['pan']))
                <span class="label">PAN:</span> {{ $company['pan'] }}<br>
            @endif
            @if (filled($company['sac']))
                <span class="label">SAC CODE:</span> {{ $company['sac'] }}<br>
            @endif
            @if (filled($invoice->notes))
                <span class="label">Notes:</span> {{ $invoice->notes }}<br>
            @endif
            @if (filled($invoice->terms))
                <span class="label">Terms:</span> {{ $invoice->terms }}
            @endif
        </td>

        <td colspan="3" class="signature">
            <b class="sig-co">For: "{{ strtoupper($company['name']) }}"</b>
            <br><br><br><br>

            __________________________
            <br>
            <b>Authorized Signatory</b><br>
            <b>Director</b>
        </td>
    </tr>

</table>

</body>
</html>