<?php

namespace App\Models;

use Illuminate\Notifications\DatabaseNotification;

class AppNotification extends DatabaseNotification
{
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
