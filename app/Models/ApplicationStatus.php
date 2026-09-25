<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicationStatus extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'application_statuses';

    protected $fillable = ['name', 'slug', 'color', 'icon', 'description', 'stage_order', 'is_default', 'is_closed', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'is_closed' => 'boolean',
    ];

    public function loanApplications(): HasMany
    {
        return $this->hasMany(LoanApplication::class);
    }

    public function insuranceApplications(): HasMany
    {
        return $this->hasMany(InsuranceApplication::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")->orWhere('code', 'like', "%{$term}%");
        });
    }
}
