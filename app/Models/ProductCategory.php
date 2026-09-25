<?php

namespace App\Models;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductCategory extends Model
{
    use Auditable;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'product_id', 'name', 'slug', 'code', 'icon', 'description', 'min_amount', 'max_amount',
        'min_tenure_months', 'max_tenure_months', 'default_roi', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'default_roi' => 'decimal:3',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function subcategories(): HasMany
    {
        return $this->hasMany(ProductSubcategory::class)->orderBy('sort_order');
    }

    public function lenderProducts(): HasMany
    {
        return $this->hasMany(LenderProduct::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForProduct(Builder $query, $product): Builder
    {
        $id = $product instanceof Product ? $product->getKey() : $product;

        return $query->where('product_id', $id);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function activeSubcategoryCount(): int
    {
        return $this->subcategories()->where('is_active', true)->count();
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where('name', 'like', '%' . $term . '%');
    }
}
