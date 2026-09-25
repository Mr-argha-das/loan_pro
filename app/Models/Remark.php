<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Remark extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['remarkable_type', 'remarkable_id', 'body', 'type', 'is_internal', 'is_pinned', 'created_by'];

    protected $casts = [
        'is_internal' => 'boolean',
        'is_pinned' => 'boolean',
    ];

    public function remarkable(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Alias kept for templates written before the rename.
     */
    public function author(): BelongsTo
    {
        return $this->creator();
    }

    public function typeColor(): string
    {
        return match ($this->type) {
            'status' => 'info',
            'internal' => 'secondary',
            'system' => 'dark',
            default => 'primary',
        };
    }
}
