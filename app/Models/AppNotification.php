<?php

namespace App\Models;

use Illuminate\Notifications\DatabaseNotification;

class AppNotification extends DatabaseNotification
{
    /*
     * Laravel's database channel stores only the JSON `data` column, so the LoanPro fields
     * (title, message, module, url, ...) are read from that payload when the column is empty.
     */
    protected function payloadValue(string $key): mixed
    {
        if (! empty($this->attributes[$key] ?? null)) {
            return $this->attributes[$key];
        }

        $payload = $this->getAttribute('data');

        return is_array($payload) ? ($payload[$key] ?? null) : null;
    }

    public function getTitleAttribute(): ?string
    {
        return $this->payloadValue('title');
    }

    public function getMessageAttribute(): ?string
    {
        return $this->payloadValue('message');
    }

    public function getModuleAttribute(): ?string
    {
        return $this->payloadValue('module');
    }

    public function getIconAttribute(): ?string
    {
        return $this->payloadValue('icon');
    }

    public function getColorAttribute(): ?string
    {
        return $this->payloadValue('color');
    }

    public function getUrlAttribute(): ?string
    {
        return $this->payloadValue('url');
    }

    public function getSeverityAttribute(): ?string
    {
        return $this->payloadValue('severity');
    }

    public function getNotificationTypeAttribute(): ?string
    {
        return $this->payloadValue('notification_type');
    }

    /** Extra context saved with the notification (ids, amounts, etc.). */
    public function context(): array
    {
        $context = $this->payloadValue('data');

        return is_array($context) ? $context : [];
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function typeMaster()
    {
        return $this->belongsTo(NotificationType::class, 'notification_type', 'slug');
    }

    public function color(): string
    {
        return $this->color ?: 'primary';
    }

    public function severityBadge(): string
    {
        return match ($this->severity) {
            'success' => 'success',
            'warning' => 'warning',
            'error', 'danger' => 'danger',
            default => 'info',
        };
    }
}
