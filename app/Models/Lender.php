<?php

namespace App\Models;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lender extends Model
{
    use Auditable;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name', 'code', 'logo_path', 'lender_type', 'online_status', 'contact_person', 'contact_number',
        'contact_email', 'website', 'processing_time_days', 'min_ticket_size', 'max_ticket_size',
        'address', 'city', 'remarks', 'status', 'sort_order',
    ];

    protected $casts = [
        'min_ticket_size' => 'decimal:2',
        'max_ticket_size' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        // `code` is NOT NULL + unique in the DB. When the admin leaves it blank, generate one;
        // on edit, keep the existing code so a blank field never wipes it.
        static::saving(function (Lender $lender) {
            if (filled($lender->code)) {
                return;
            }

            $lender->code = $lender->exists
                ? $lender->getOriginal('code')
                : self::generateCode();
        });
    }

    protected static function generateCode(): string
    {
        do {
            $code = 'LND-'.strtoupper(\Illuminate\Support\Str::random(6));
        } while (static::withTrashed()->where('code', $code)->exists());

        return $code;
    }

    public function products(): HasMany
    {
        return $this->hasMany(LenderProduct::class)->orderBy('product_name');
    }

    public function loanLenders(): HasMany
    {
        return $this->hasMany(LoanLender::class);
    }

    public function disbursements(): HasMany
    {
        return $this->hasMany(Disbursement::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeOnline(Builder $query): Builder
    {
        return $query->where('online_status', 'online');
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->logo_path)
            : null;
    }

    public function isOnline(): bool
    {
        return $this->online_status === 'online';
    }

    public function initials(): string
    {
        return strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $this->name) ?: 'LB', 0, 2));
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
