<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Monthly employee payout: salary + incentive, where the incentive is based on
 * the number of approved leads the employee handled in that month
 * (total coins = approved leads x coins per lead).
 */
class EmployeePayout extends Model
{
    use SoftDeletes;

    public const STATUSES = ['unpaid' => 'Unpaid', 'paid' => 'Paid'];

    protected $fillable = [
        'payout_code', 'employee_id', 'payout_month', 'approved_leads', 'coins_per_lead', 'total_coins',
        'monthly_salary', 'incentive_amount', 'total_amount', 'status', 'paid_on', 'notes', 'created_by',
    ];

    protected $casts = [
        'payout_month' => 'date',
        'paid_on' => 'date',
        'approved_leads' => 'integer',
        'coins_per_lead' => 'decimal:2',
        'total_coins' => 'decimal:2',
        'monthly_salary' => 'decimal:2',
        'incentive_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForMonth(Builder $query, string $month): Builder
    {
        return $query->whereDate('payout_month', $month.'-01');
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
