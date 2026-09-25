<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\NotificationType;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * Thin wrapper around Laravel notifications that keeps the notification
 * centre metadata (module, icon, colour, action url) in one place.
 */
class NotificationService
{
    public const TYPE_MAP = [
        'lead-assigned' => ['Lead Assigned', 'person-plus', 'primary', 'leads'],
        'lead-updated' => ['Lead Updated', 'pencil-square', 'info', 'leads'],
        'document-uploaded' => ['Document Uploaded', 'file-earmark-arrow-up', 'info', 'documents'],
        'document-verified' => ['Document Verified', 'patch-check', 'success', 'documents'],
        'application-created' => ['Application Created', 'clipboard-plus', 'primary', 'applications'],
        'application-status-changed' => ['Application Status Changed', 'arrow-repeat', 'warning', 'applications'],
        'loan-approved' => ['Loan Approved', 'check-circle', 'success', 'applications'],
        'loan-rejected' => ['Loan Rejected', 'x-circle', 'danger', 'applications'],
        'disbursement-completed' => ['Disbursement Completed', 'cash-stack', 'success', 'disbursements'],
        'payment-received' => ['Payment Received', 'wallet2', 'success', 'payments'],
        'invoice-generated' => ['Invoice Generated', 'receipt', 'primary', 'invoices'],
    ];

    /** @param User|\Illuminate\Support\Collection|array $recipients */
    public function send($recipients, string $typeSlug, string $title, string $message, array $context = []): void
    {
        $recipients = collect($recipients instanceof Collection ? $recipients->all() : (array) $recipients)
            ->filter()
            ->unique('id')
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        [$name, $icon, $color, $module] = $this->resolve($typeSlug, $context);
        $master = NotificationType::query()->where('slug', $typeSlug)->first();

        $notification = new \App\Notifications\LoanProNotification(
            typeSlug: $typeSlug,
            title: $context['title'] ?? $title,
            message: $message,
            module: $context['module'] ?? $module,
            icon: $context['icon'] ?? ($master->icon ?? $icon),
            color: $context['color'] ?? ($master->color ?? $color),
            url: $context['url'] ?? null,
            severity: $context['severity'] ?? $this->severityFor($typeSlug),
            data: $context['data'] ?? [],
            actorId: $context['actor_id'] ?? auth()->id(),
        );

        Notification::send($recipients, $notification);
    }

    protected function resolve(string $slug, array $context): array
    {
        $config = self::TYPE_MAP[$slug] ?? ['Notification', 'bell', 'primary', $context['module'] ?? 'general'];

        return [$config[0], $config[1], $config[2], $config[3]];
    }

    protected function severityFor(string $slug): string
    {
        return match (true) {
            str_contains($slug, 'rejected') => 'error',
            str_contains($slug, 'approved'), str_contains($slug, 'verified'), str_contains($slug, 'completed'), str_contains($slug, 'received') => 'success',
            str_contains($slug, 'assigned'), str_contains($slug, 'created') => 'info',
            default => 'warning',
        };
    }

    public function unreadCountFor(User $user): int
    {
        return AppNotification::query()
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    public function recentFor(User $user, int $limit = 8)
    {
        return AppNotification::query()
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->latest()
            ->limit($limit)
            ->get();
    }
}
