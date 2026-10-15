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
                                <details class="mt-1">
                                    <summary class="small text-primary" style="cursor:pointer">View details</summary>
                                    <div class="small mt-2">
                                        <div><strong>Type:</strong> {{ $notification->notification_type ?? '—' }}</div>
                                        <div><strong>Message:</strong> {{ $notification->message ?? '—' }}</div>
                                        <div><strong>Received:</strong> {{ $notification->created_at?->format('d M Y, h:i A') ?? '—' }}</div>
                                        @if (! empty($notification->context()))
                                            <table class="table table-sm mt-2 mb-0">
                                                <tbody>
                                                    @foreach ($notification->context() as $key => $value)
                                                        <tr>
                                                            <td class="text-muted text-nowrap">{{ \Illuminate\Support\Str::headline($key) }}</td>
                                                            <td>{{ is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (is_bool($value) ? ($value ? 'Yes' : 'No') : ($value ?? '—')) }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        @endif
                                    </div>
                                </details>
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
