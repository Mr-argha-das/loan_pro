<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class DocumentType extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name', 'code', 'applies_to', 'is_required_default', 'has_expiry', 'allowed_extensions',
        'max_size_kb', 'description', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_required_default' => 'boolean',
        'has_expiry' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function extensionList(): array
    {
        return array_filter(array_map('trim', explode(',', (string) $this->allowed_extensions)));
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where('name', 'like', '%' . $term . '%');
    }
}
