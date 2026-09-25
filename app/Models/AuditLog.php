<?php

namespace App\Models;

use App\Concerns\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use Filterable;
    use HasFactory;
    protected $filterDateColumn = 'created_at';

    protected $fillable = [
        'user_id', 'user_name', 'action', 'module', 'record_type', 'record_id', 'record_label',
        'old_values', 'new_values', 'ip_address', 'user_agent', 'description',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function changedFields(): array
    {
        $old = $this->old_values ?? [];
        $new = $this->new_values ?? [];
        $keys = array_unique([...array_keys($old), ...array_keys($new)]);
        $diff = [];

        foreach ($keys as $key) {
            if (($old[$key] ?? null) != ($new[$key] ?? null)) {
                $diff[$key] = ['from' => $old[$key] ?? null, 'to' => $new[$key] ?? null];
            }
        }

        return $diff;
    }

    public function actionColor(): string
    {
        return match (true) {
            str_contains($this->action, 'deleted') => 'danger',
            str_contains($this->action, 'created') => 'success',
            str_contains($this->action, 'updated') => 'warning',
            default => 'info',
        };
    }
}
