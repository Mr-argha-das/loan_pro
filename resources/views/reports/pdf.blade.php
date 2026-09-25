<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $reportName }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #172B4D; margin: 0; padding: 22px 26px; }
        .head { border-bottom: 2px solid #1677FF; padding-bottom: 10px; margin-bottom: 14px; display: flex; justify-content: space-between; }
        .brand { font-size: 15px; font-weight: bold; color: #0B1F3A; }
        .muted { color: #7A8CA5; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #F5F8FC; text-align: left; padding: 5px 6px; border-bottom: 1px solid #E5EBF3; font-size: 8.5px; text-transform: uppercase; letter-spacing: .04em; }
        td { padding: 5px 6px; border-bottom: 1px solid #EEF2F7; }
        tr:nth-child(even) td { background: #FBFCFE; }
        .meta { margin-bottom: 10px; font-size: 9px; color: #7A8CA5; }
        .meta span { margin-right: 16px; }
    </style>
</head>
<body>
    <div class="head">
        <div>
            <div class="brand">{{ $company['name'] ?? config('app.name') }}</div>
            <div class="muted">{{ $company['address'] ?? 'Finance & Insurance Management System' }}</div>
        </div>
        <div style="text-align:right">
            <div style="font-size:12px;font-weight:bold">{{ $reportName }}</div>
            <div class="muted">Generated {{ $generatedAt ?? \App\Support\Format::dateTime(now()) }}</div>
            <div class="muted">By {{ $generatedBy ?? 'System' }}</div>
        </div>
    </div>

    @if (! empty($meta))
        <div class="meta">
            @foreach ($meta as $label => $value)
                @continue(is_array($value))
                <span><strong>{{ \App\Support\Format::titleCase($label) }}:</strong> {{ $value }}</span>
            @endforeach
        </div>
    @endif

    <table>
        <thead>
            <tr>
                @foreach ($columns as $column)
                    <th>{{ is_array($column) ? ($column['label'] ?? '') : $column }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @php
                $amountPattern = '/(amount|value|premium|income|disbursed|collected|balance|total|fee|salary)/i';
                $labels = collect(array_keys($columns))->map(fn ($index) => is_array($columns[$index] ?? null) ? ($columns[$index]['label'] ?? '') : ($columns[$index] ?? ''));
            @endphp
            @foreach ($rows as $row)
                <tr>
                    @foreach (array_values($row) as $index => $cell)
                        @php
                            $label = (string) ($labels[$index] ?? '');
                            $numeric = is_numeric($cell);
                            $isMoney = $numeric && preg_match($amountPattern, $label);
                        @endphp
                        <td style="{{ $isMoney ? 'text-align:right' : '' }}">
                            @if ($cell === null || $cell === '')
                                —
                            @elseif ($isMoney)
                                {{ \App\Support\Format::money((float) $cell) }}
                            @elseif ($numeric && (float) $cell == floor((float) $cell))
                                {{ number_format((float) $cell) }}
                            @else
                                {{ $cell }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
