<?php

namespace App\Concerns;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

/**
 * Writes an audit trail entry for every created / updated / deleted record.
 *
 * Only the attributes that actually changed are persisted, and sensitive
 * columns (passwords, tokens) are never logged.
 */
trait Auditable
{
    protected static array $auditExcluded = ['password', 'remember_token', 'updated_at', 'created_at'];

    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            $model->writeAudit('created', [], $model->auditSnapshot($model->getAttributes()));
        });

        static::updated(function ($model) {
            $old = [];
            $new = [];

            foreach ($model->getChanges() as $key => $value) {
                if (in_array($key, self::$auditExcluded, true)) {
                    continue;
                }

                $old[$key] = $model->getOriginal($key);
                $new[$key] = $value;
            }

            if ($new) {
                $model->writeAudit('updated', $old, $new);
            }
        });

        static::deleted(function ($model) {
            $model->writeAudit('deleted', $model->auditSnapshot($model->getOriginal()), []);
        });
    }

    protected function auditSnapshot(array $attributes): array
    {
        return collect($attributes)
            ->except([...self::$auditExcluded, 'id'])
            ->map(fn ($value) => is_scalar($value) || $value === null ? $value : '[value]')
            ->all();
    }

    protected function writeAudit(string $action, array $old, array $new): void
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests() && ! request()->hasHeader('X-Audit')) {
            // Still record console actions (seeders / commands) but keep them terse.
        }

        try {
            $user = Auth::user();
            $label = $this->auditLabel();

            AuditLog::query()->create([
                'user_id' => $user?->id,
                'user_name' => $user?->name ?? 'System',
                'action' => $action,
                'module' => $this->auditModule(),
                'record_type' => static::class,
                'record_id' => $this->getKey(),
                'record_label' => $label,
                'old_values' => $old ?: null,
                'new_values' => $new ?: null,
                'ip_address' => request()->ip(),
                'user_agent' => substr((string) request()->userAgent(), 0, 250),
                'description' => trim(sprintf('%s %s %s', $user?->name ?? 'System', $action, $label)),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function auditModule(): string
    {
        return \Illuminate\Support\Str::headline(class_basename(static::class));
    }

    public function auditLabel(): string
    {
        foreach (['name', 'title', 'lead_code', 'application_code', 'invoice_number', 'payment_code', 'disbursement_code', 'customer_code', 'employee_code', 'code', 'slug'] as $attribute) {
            if (! empty($this->{$attribute})) {
                return (string) $this->{$attribute};
            }
        }

        return class_basename(static::class).' #'.$this->getKey();
    }
}
