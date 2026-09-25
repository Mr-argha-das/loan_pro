<?php

namespace App\Models;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LenderProduct extends Model
{
    use Auditable;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'lender_id', 'product_id', 'product_category_id', 'product_subcategory_id', 'product_name',
        'code', 'loan_type', 'min_amount', 'max_amount', 'min_tenure_months', 'max_tenure_months',
        'roi', 'apr', 'processing_fee', 'processing_fee_type', 'penal_charge', 'penal_charge_type',
        'min_credit_score', 'min_monthly_income', 'eligibility', 'required_documents', 'remarks',
        'is_featured', 'status', 'sort_order',
    ];

    protected $casts = [
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'roi' => 'decimal:3',
        'apr' => 'decimal:3',
        'processing_fee' => 'decimal:3',
        'penal_charge' => 'decimal:3',
        'min_monthly_income' => 'decimal:2',
        'required_documents' => 'array',
        'is_featured' => 'boolean',
    ];

    public function lender(): BelongsTo
    {
        return $this->belongsTo(Lender::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(ProductSubcategory::class, 'product_subcategory_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /** Indicative EMI for the given principal / tenure (reducing balance). */
    public function calculateEmi(float $amount, ?int $tenureMonths = null): float
    {
        $months = $tenureMonths ?: ($this->max_tenure_months ?: 12);
        $annualRate = (float) ($this->roi ?: 0);

        return self::emi($amount, $annualRate, $months);
    }

    public static function emi(float $principal, float $annualRate, int $months): float
    {
        if ($months <= 0) {
            return 0.0;
        }

        $monthlyRate = $annualRate / 12 / 100;

        if ($monthlyRate <= 0) {
            return round($principal / $months, 2);
        }

        $factor = ((1 + $monthlyRate) ** $months);

        return round($principal * $monthlyRate * $factor / ($factor - 1), 2);
    }

    public function processingFeeAmount(float $amount): float
    {
        if (! $this->processing_fee) {
            return 0.0;
        }

        return $this->processing_fee_type === 'fixed'
            ? (float) $this->processing_fee
            : round($amount * (float) $this->processing_fee / 100, 2);
    }
}
