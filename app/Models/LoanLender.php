<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoanLender extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'lead_id', 'loan_application_id', 'lender_id', 'lender_product_id', 'loan_amount',
        'tenure_months', 'roi', 'apr', 'emi', 'processing_fee', 'penal_charge', 'other_charges',
        'required_documents', 'is_selected', 'is_primary', 'status', 'remarks', 'created_by',
    ];

    protected $casts = [
        'loan_amount' => 'decimal:2',
        'roi' => 'decimal:3',
        'apr' => 'decimal:3',
        'emi' => 'decimal:2',
        'processing_fee' => 'decimal:2',
        'penal_charge' => 'decimal:3',
        'other_charges' => 'decimal:2',
        'required_documents' => 'array',
        'is_selected' => 'boolean',
        'is_primary' => 'boolean',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class, 'loan_application_id');
    }

    public function lender(): BelongsTo
    {
        return $this->belongsTo(Lender::class);
    }

    public function lenderProduct(): BelongsTo
    {
        return $this->belongsTo(LenderProduct::class);
    }
}
