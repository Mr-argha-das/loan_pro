<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

/** Insurance sub-category ("Term Life Insurance", ...). */
class InsurancePlan extends ProductSubcategory
{
    protected $table = 'product_subcategories';

    protected static function booted(): void
    {
        static::addGlobalScope('insurance_plans', function (Builder $query) {
            $query->whereHas('product', fn (Builder $q) => $q->where('slug', Product::INSURANCE));
        });
    }
}
