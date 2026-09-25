<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * A loan category ("Personal Loan", "Home Loan", ...) - stored in the shared
 * product hierarchy (product_categories) so the Admin can extend the loan
 * catalogue without code changes.
 */
class LoanType extends ProductCategory
{
    protected $table = 'product_categories';

    protected static function booted(): void
    {
        static::addGlobalScope('loans_only', function (Builder $query) {
            $query->whereHas('product', fn (Builder $q) => $q->where('slug', Product::LOANS));
        });
    }

    public static function query(): Builder
    {
        return parent::query()->whereHas('product', fn (Builder $q) => $q->where('slug', Product::LOANS));
    }
}
