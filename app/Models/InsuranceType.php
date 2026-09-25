<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An insurance category ("Life Insurance", ...) - shared hierarchy, scoped to
 * the Insurance product.
 */
class InsuranceType extends ProductCategory
{
    protected $table = 'product_categories';

    protected static function booted(): void
    {
        static::addGlobalScope('insurance_only', function (Builder $query) {
            $query->whereHas('product', fn (Builder $q) => $q->where('slug', Product::INSURANCE));
        });
    }
}
