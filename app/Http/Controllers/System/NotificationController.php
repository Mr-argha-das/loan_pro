<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\NotificationType;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(protected NotificationService $notifications)
    {
    }

    public function index(Request $request): View|JsonResponse
    {
        abort_unless($request->user()->hasPermissionTo('notifications.view'), 403);

        $user = $request->user();

        $query = AppNotification::query()
            ->where('notifiable_type', $user::class)
            ->where('notifiable_id', $user->id)
            ->when($request->filled('module'), fn ($q) => $q->where('module', $request->string('module')))
            ->when($request->filled('type'), fn ($q) => $q->where('notification_type', $request->string('type')))
            ->when($request->boolean('unread_only'), fn ($q) => $q->whereNull('read_at'))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($sub) => $sub
                ->where('title', 'like', '%'.$request->string('q').'%')
                ->orWhere('message', 'like', '%'.$request->string('q').'%')))
            ->latest();

        $notifications = $query->paginate($this->perPage($request))->withQueryString();

        if ($request->expectsJson()) {
            return $this->tablePayload($notifications, 'notifications.partials.table');
        }

        return view('notifications.index', [
            'notifications' => $notifications,
            'types' => NotificationType::query()->active()->ordered()->get(),
            'modules' => AppNotification::query()
                ->where('notifiable_id', $user->id)
                ->distinct()
                ->orderBy('module')
                ->pluck('module')
                ->filter(),
            'unreadCount' => $this->notifications->unreadCountFor($user),
            'filters' => $request->all(),
        ]);
    }

    /** Bell dropdown feed. */
    public function feed(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'unread' => $this->notifications->unreadCountFor($user),
            'html' => view('notifications.partials.feed', [
                'notifications' => $this->notifications->recentFor($user, 8),
            ])->render(),
        ]);
    }

    public function markRead(Request $request, AppNotification $notification): JsonResponse|RedirectResponse
    {
        $this->guard($request, $notification);

        $notification->markAsRead();

        if ($request->expectsJson()) {
            return $this->ok('Notification marked as read.', [
                'unread' => $this->notifications->unreadCountFor($request->user()),
            ]);
        }

        return redirect($notification->url ?: route('notifications.index'));
    }

    public function markAllRead(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        AppNotification::query()
            ->where('notifiable_type', $user::class)
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now(), 'updated_at' => now()]);

        if ($request->expectsJson()) {
            return $this->ok('All notifications marked as read.', ['unread' => 0]);
        }

        return back()->with('success', 'All notifications marked as read.');
    }

    public function destroy(Request $request, AppNotification $notification): JsonResponse|RedirectResponse
    {
        $this->guard($request, $notification);

        $notification->delete();

        if ($request->expectsJson()) {
            return $this->ok('Notification removed.', ['unread' => $this->notifications->unreadCountFor($request->user())]);
        }

        return back()->with('success', 'Notification removed.');
    }

    protected function guard(Request $request, AppNotification $notification): void
    {
        abort_unless(
            (int) $notification->notifiable_id === (int) $request->user()->id,
            403,
            'This notification belongs to another user.'
        );
    }
}
