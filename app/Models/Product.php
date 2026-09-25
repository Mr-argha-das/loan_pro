<?php

namespace App\Models;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use Auditable;
    use HasFactory;
    use SoftDeletes;

    public const LOANS = 'loans';
    public const INSURANCE = 'insurance';
    public const CARDS = 'cards';
    public const REAL_ESTATE = 'real-estate';

    protected $fillable = [
        'name', 'slug', 'code', 'icon', 'icon_set', 'theme', 'tagline', 'description',
        'card_gradient_from', 'card_gradient_to', 'category_label', 'subcategory_label',
        'is_active', 'sort_order',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function categories(): HasMany
    {
        return $this->hasMany(ProductCategory::class)->orderBy('sort_order');
    }

    public function subcategories(): HasMany
    {
        return $this->hasMany(ProductSubcategory::class);
    }

    public function lenderProducts(): HasMany
    {
        return $this->hasMany(LenderProduct::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function activeCategoryCount(): int
    {
        return $this->categories()->where('is_active', true)->count();
    }
}
