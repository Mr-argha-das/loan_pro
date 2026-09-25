@extends('layouts.app')

@section('title', $reportName)
@section('page-header', true)
@section('page-title', $reportName)
@section('page-subtitle', number_format(count($rows)).' rows · generated '.\App\Support\Format::dateTime(now()))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">Reports</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $reportName }}</li>
@endsection

@section('page-actions')
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('reports.export', array_merge(['report' => $reportKey], $filters)) }}" class="btn btn-light btn-sm"><i class="bi bi-file-earmark-excel"></i> Excel</a>
        <a href="{{ route('reports.pdf', array_merge(['report' => $reportKey], $filters)) }}" class="btn btn-light btn-sm"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
        <button type="button" class="btn btn-light btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
    </div>
@endsection

@section('content')
    <x-filter-panel :action="route('reports.show', $reportKey)" :reset-url="route('reports.show', $reportKey)">
        <div class="row g-2 align-items-end">
            <div class="col-md-2"><label class="form-label" for="from_date">From</label><input type="date" class="form-control" id="from_date" name="from_date" value="{{ $filters['from_date'] ?? '' }}"></div>
            <div class="col-md-2"><label class="form-label" for="to_date">To</label><input type="date" class="form-control" id="to_date" name="to_date" value="{{ $filters['to_date'] ?? '' }}"></div>
            <div class="col-md-2">
                <label class="form-label" for="product_id">Product</label>
                <select class="form-select" id="product_id" name="product_id">
                    <option value="">All products</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" @selected((int) ($filters['product_id'] ?? 0) === $product->id)>{{ $product->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="category_id">Category</label>
                <select class="form-select" id="category_id" name="category_id">
                    <option value="">All categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((int) ($filters['category_id'] ?? 0) === $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="lender_id">Lender</label>
                <select class="form-select" id="lender_id" name="lender_id">
                    <option value="">All lenders</option>
                    @foreach ($lenders as $lender)
                        <option value="{{ $lender->id }}" @selected((int) ($filters['lender_id'] ?? 0) === $lender->id)>{{ $lender->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="assigned_to">Employee</label>
                <select class="form-select" id="assigned_to" name="assigned_to">
                    <option value="">Everyone</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}" @selected((int) ($filters['assigned_to'] ?? 0) === $employee->id)>{{ $employee->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="status">Status</label>
                <input type="text" class="form-control" id="status" name="status" value="{{ $filters['status'] ?? '' }}" placeholder="Any status">
            </div>
            <div class="col-md-2"><label class="form-label" for="city">City</label><input type="text" class="form-control" id="city" name="city" value="{{ $filters['city'] ?? '' }}" placeholder="Any city"></div>
        </div>
    </x-filter-panel>

    @php
        $amountPattern = '/(amount|value|premium|income|disbursed|collected|balance|total|fee|salary)/i';
        $labels = collect(array_keys($columns))->map(fn ($index) => is_array($columns[$index] ?? null) ? ($columns[$index]['label'] ?? '') : ($columns[$index] ?? ''));
        $moneyTotals = $labels
            ->filter(fn ($label) => preg_match($amountPattern, (string) $label))
            ->mapWithKeys(function ($label, $index) use ($rows) {
                $sum = collect($rows)->sum(fn ($row) => (float) (array_values($row)[$index] ?? 0));
                return [$label => $sum];
            })
            ->filter(fn ($sum) => $sum > 0)
            ->take(3);
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <x-stat-card label="Rows in report" :value="number_format(count($rows))" icon="bi-list-ol" tone="primary" />
        </div>
        @foreach ($moneyTotals as $label => $sum)
            <div class="col-6 col-lg-3">
                <x-stat-card :label="'Total '.\App\Support\Format::titleCase($label)" :value="\App\Support\Format::compactInr($sum)" icon="bi-cash-stack" tone="green" />
            </div>
        @endforeach
    </div>

    @if (! empty($meta))
        <div class="lp-report-meta mb-3 no-print">
            @foreach ($meta as $label => $value)
                @continue(is_array($value))
                <span><strong>{{ $label }}:</strong> {{ $value }}</span>
            @endforeach
        </div>
    @endif

    <x-card :padding="false">
        <div class="lp-table-wrap">
            <table class="lp-table">
                <thead>
                    <tr>
                        @foreach ($columns as $column)
                            <th scope="col">{{ is_array($column) ? ($column['label'] ?? '') : $column }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            @foreach (array_values($row) as $index => $cell)
                                @php
                                    $label = (string) ($labels[$index] ?? '');
                                    $numeric = is_numeric($cell);
                                    $isMoney = $numeric && preg_match($amountPattern, $label);
                                @endphp
                                <td @class(['fw-semibold' => $index === 0, 'text-end' => $isMoney])>
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
                    @empty
                        <tr>
                            <td colspan="{{ max(1, count($columns)) }}">
                                <x-empty-state icon="bi-bar-chart" title="No data for these filters" message="Widen the date range or clear a filter to see results." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
@endsection
