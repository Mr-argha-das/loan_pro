<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Shared index-page filtering: date range, single/array status, owner and
 * free-text search. Keeps controllers thin and results consistent.
 */
trait Filterable
{
    public function scopeFilter(Builder $query, Request $request, array $columns = []): Builder
    {
        if ($term = $request->string('q')->toString()) {
            $query->where(function (Builder $q) use ($term, $columns) {
                foreach ($columns as $column) {
                    $q->orWhere($column, 'like', "%{$term}%");
                }
            });
        }

        if ($status = $request->input('status')) {
            is_array($status)
                ? $query->whereIn('status', $status)
                : $query->where('status', $status);
        }

        if ($from = $request->input('from_date')) {
            $query->whereDate($this->filterDateColumn ?? 'created_at', '>=', $from);
        }

        if ($to = $request->input('to_date')) {
            $query->whereDate($this->filterDateColumn ?? 'created_at', '<=', $to);
        }

        return $query;
    }

    public function scopeDateBetween(Builder $query, ?string $from, ?string $to, string $column = 'created_at'): Builder
    {
        return $query
            ->when($from, fn (Builder $q) => $q->whereDate($column, '>=', $from))
            ->when($to, fn (Builder $q) => $q->whereDate($column, '<=', $to));
    }
}
