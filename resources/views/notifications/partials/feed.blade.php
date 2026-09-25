@forelse ($notifications as $notification)
    <a href="{{ route('notifications.read', $notification) }}" class="d-flex gap-3 px-3 py-2 border-bottom text-decoration-none {{ $notification->read_at ? 'opacity-75' : '' }}">
        <span class="lp-tone-{{ $notification->color ?? 'primary' }} rounded-circle d-grid flex-shrink-0" style="width:36px;height:36px;place-items:center">
            <i class="bi bi-{{ $notification->icon ?? 'bell' }}"></i>
        </span>
        <span class="flex-grow-1">
            <span class="d-block fw-semibold text-body" style="font-size:.83rem">{{ $notification->title }}</span>
            <span class="d-block text-muted" style="font-size:.75rem">{{ \Illuminate\Support\Str::limit($notification->message, 72) }}</span>
            <span class="d-block text-muted" style="font-size:.7rem">{{ $notification->created_at?->diffForHumans() }}</span>
        </span>
        @if (! $notification->read_at)
            <span class="align-self-center rounded-circle bg-primary" style="width:8px;height:8px" aria-label="Unread"></span>
        @endif
    </a>
@empty
    <div class="lp-empty py-4"><i class="bi bi-bell-slash"></i>No notifications yet</div>
@endforelse
