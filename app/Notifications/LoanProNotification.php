<?php

namespace App\Notifications;

use App\Models\AppNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Every LoanPro notification is stored for the in-app notification centre.
 * Channels are centralised so SMS / mail / push can be added later without
 * touching the call sites.
 */
class LoanProNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $typeSlug,
        public string $title,
        public string $message,
        public string $module = 'general',
        public string $icon = 'bell',
        public string $color = 'primary',
        public ?string $url = null,
        public string $severity = 'info',
        public array $data = [],
        public ?int $actorId = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'notification_type' => $this->typeSlug,
            'title' => $this->title,
            'message' => $this->message,
            'module' => $this->module,
            'icon' => $this->icon,
            'color' => $this->color,
            'url' => $this->url,
            'severity' => $this->severity,
            'actor_id' => $this->actorId,
            'data' => $this->data,
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    public static function feedLabel(AppNotification $notification): string
    {
        return $notification->title ?: Str::headline($notification->type ?? 'Notification');
    }
}
