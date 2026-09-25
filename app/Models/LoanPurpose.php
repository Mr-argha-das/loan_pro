<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A loan purpose ("Marriage", "Medical", ...) - stored in product_subcategories
 * under the matching loan category.
 */
class LoanPurpose extends ProductSubcategory
{
    protected $table = 'product_subcategories';

    protected static function booted(): void
    {
        static::addGlobalScope('loan_purposes', function (Builder $query) {
            $query->whereHas('product', fn (Builder $q) => $q->where('slug', Product::LOANS));
        });
    }

    public function loanType(): BelongsTo
    {
        return $this->belongsTo(LoanType::class, 'product_category_id');
    }
}
