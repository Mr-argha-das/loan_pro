<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LenderApplicationDetail extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'loan_application_id', 'loan_lender_id', 'lender_id', 'reference_number', 'status',
        'requested_amount', 'sanctioned_amount', 'roi', 'tenure_months', 'processing_fee',
        'submitted_at', 'sanctioned_at', 'rejected_at', 'rejection_reason', 'meta', 'remarks',
        'handled_by',
    ];

    protected $casts = [
        'requested_amount' => 'decimal:2',
        'sanctioned_amount' => 'decimal:2',
        'roi' => 'decimal:3',
        'processing_fee' => 'decimal:2',
        'submitted_at' => 'datetime',
        'sanctioned_at' => 'datetime',
        'rejected_at' => 'datetime',
        'meta' => 'array',
    ];

    public function lender(): BelongsTo
    {
        return $this->belongsTo(Lender::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class, 'loan_application_id');
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
