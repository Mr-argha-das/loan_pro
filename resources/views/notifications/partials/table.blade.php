@php $items = $items ?? ($notifications ?? collect()); @endphp

<div class="lp-table-wrap">
    <table class="lp-table">
        <thead>
            <tr>
                <th scope="col">Notification</th>
                <th scope="col">Module</th>
                <th scope="col">Severity</th>
                <th scope="col">Received</th>
                <th scope="col">State</th>
                <th scope="col" class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $notification)
                <tr class="{{ $notification->read_at ? '' : 'lp-row-unread' }}">
                    <td>
                        <div class="d-flex align-items-start gap-3">
                            <span class="lp-notif-icon lp-tone-{{ $notification->color ?? 'primary' }}">
                                <i class="bi {{ $notification->icon ?? 'bi-bell' }}"></i>
                            </span>
                            <div>
                                <div class="fw-semibold">{{ $notification->title }}</div>
                                <div class="text-muted" style="font-size:.78rem">{{ $notification->message }}</div>
                                @if ($notification->url)
                                    <a href="{{ $notification->url }}" class="small">Open record <i class="bi bi-arrow-right"></i></a>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td>{{ \App\Support\Format::titleCase($notification->module ?? 'system') }}</td>
                    <td>
                        <span class="lp-badge bg-{{ $notification->severity === 'danger' ? 'danger' : ($notification->severity === 'warning' ? 'warning' : 'primary') }}-subtle bg-opacity-10 text-{{ $notification->severity === 'danger' ? 'danger' : ($notification->severity === 'warning' ? 'warning' : 'primary') }}">
                            {{ \App\Support\Format::titleCase($notification->severity ?? 'info') }}
                        </span>
                    </td>
                    <td class="text-muted text-nowrap">{{ \App\Support\Format::humanize($notification->created_at) }}</td>
                    <td>
                        @if ($notification->read_at)
                            <span class="lp-badge bg-secondary-subtle text-secondary bg-opacity-10">Read</span>
                        @else
                            <span class="lp-badge bg-primary-subtle text-primary bg-opacity-10">Unread</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="btn-group">
                            @unless ($notification->read_at)
                                <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-light" title="Mark read"><i class="bi bi-check2"></i></button>
                                </form>
                            @endunless
                            <form method="POST" action="{{ route('notifications.destroy', $notification) }}" data-confirm="Delete this notification?">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-light text-danger" title="Delete"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6"><x-empty-state icon="bi-bell-slash" title="You're all caught up" message="Lead, application and payment alerts will appear here." /></td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('partials.pagination-bar', ['items' => $items])
