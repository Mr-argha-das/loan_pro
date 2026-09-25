<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_number }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * { box-sizing: border-box; }
        @page { margin: 12mm; }
        body { font-family: 'Helvetica Neue', Arial, sans-serif; font-size: 11px; color: #172B4D; margin: 0; padding: 28px 32px; }
        .head { display: flex; justify-content: space-between; border-bottom: 2px solid #1677FF; padding-bottom: 12px; margin-bottom: 18px; }
        .brand { font-size: 17px; font-weight: bold; color: #0B1F3A; }
        .muted { color: #7A8CA5; }
        h1 { font-size: 20px; margin: 0 0 4px; letter-spacing: 1px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th { background: #F5F8FC; text-align: left; padding: 7px 8px; border-bottom: 1px solid #E5EBF3; font-size: 10px; text-transform: uppercase; letter-spacing: .05em; }
        td { padding: 7px 8px; border-bottom: 1px solid #EEF2F7; }
        .right { text-align: right; }
        .totals { width: 46%; margin-left: auto; margin-top: 14px; }
        .totals td { border: none; padding: 4px 0; }
        .totals .grand td { border-top: 1px solid #E5EBF3; padding-top: 8px; font-size: 13px; font-weight: bold; color: #1677FF; }
        .meta { display: flex; gap: 28px; margin-bottom: 12px; }
        .meta div { flex: 1; }
        .label { font-size: 9px; text-transform: uppercase; letter-spacing: .08em; color: #7A8CA5; margin-bottom: 3px; }
        .terms { margin-top: 20px; font-size: 9.5px; color: #7A8CA5; white-space: pre-line; border-top: 1px solid #E5EBF3; padding-top: 10px; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 10px; background: #E8F1FF; color: #1677FF; font-size: 9px; text-transform: uppercase; letter-spacing: .06em; }
    </style>
</head>
<body>
    <div class="head">
        <div>
            <div class="brand">{{ $company['name'] }}</div>
            <div class="muted">{{ $company['address'] }}</div>
            <div class="muted">
                {{ $company['email'] }}@if ($company['phone']) · {{ $company['phone'] }}@endif
                @if ($company['gst'])<br>GSTIN: {{ $company['gst'] }}@endif
            </div>
        </div>
        <div class="right">
            <h1>INVOICE</h1>
            <div><strong>{{ $invoice->invoice_number }}</strong></div>
            <div class="muted">Date: {{ \App\Support\Format::date($invoice->invoice_date) }}</div>
            <div class="muted">Due: {{ \App\Support\Format::date($invoice->due_date) }}</div>
            <div style="margin-top:6px"><span class="badge">{{ \App\Support\Format::titleCase($invoice->status) }}</span></div>
        </div>
    </div>

    <div class="meta">
        <div>
            <div class="label">Billed to</div>
            <div><strong>{{ $invoice->customer?->name }}</strong></div>
            <div class="muted">
                {{ $invoice->customer?->address }}<br>
                {{ collect([$invoice->customer?->city, $invoice->customer?->pincode])->filter()->implode(' - ') }}<br>
                {{ $invoice->customer?->mobile }} · {{ $invoice->customer?->email }}
            </div>
        </div>
        <div>
            <div class="label">References</div>
            <div class="muted">
                @if ($invoice->lead)Lead: {{ $invoice->lead->lead_code }}<br>@endif
                @if ($invoice->application)Application: {{ $invoice->application->application_code }}<br>@endif
                @if ($invoice->place_of_supply)Place of supply: {{ $invoice->place_of_supply }}@endif
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th class="right">Qty</th>
                <th class="right">Rate</th>
                <th class="right">Tax</th>
                <th class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="right">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td>
                    <td class="right">{{ \App\Support\Format::money($item->unit_price) }}</td>
                    <td class="right">{{ $item->tax_rate }}%</td>
                    <td class="right">{{ \App\Support\Format::money($item->line_total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td class="right">{{ \App\Support\Format::money($invoice->subtotal) }}</td></tr>
        <tr><td>Tax</td><td class="right">{{ \App\Support\Format::money($invoice->tax_amount) }}</td></tr>
        <tr><td>Discount</td><td class="right">-{{ \App\Support\Format::money($invoice->discount) }}</td></tr>
        <tr><td>Paid</td><td class="right">{{ \App\Support\Format::money($invoice->paid_amount) }}</td></tr>
        <tr class="grand"><td>Total</td><td class="right">{{ \App\Support\Format::money($invoice->total) }}</td></tr>
        <tr><td>Balance due</td><td class="right"><strong>{{ \App\Support\Format::money($invoice->balance_amount) }}</strong></td></tr>
    </table>

    @if ($invoice->terms)
        <div class="terms"><strong>Terms &amp; conditions</strong><br>{{ $invoice->terms }}</div>
    @endif

    <div class="terms">This is a computer generated invoice issued by {{ $company['name'] }}.</div>
</body>
</html>

<div style="margin-top:22px; text-align:center">
    <button onclick="window.print()" style="padding:8px 18px;border:0;border-radius:8px;background:#1677FF;color:#fff;font-size:12px;cursor:pointer">Print invoice</button>
</div>
