@php $items = $items ?? ($logs ?? collect()); @endphp

<div class="lp-table-wrap">
    <table class="lp-table">
        <thead>
            <tr>
                <th scope="col">When</th>
                <th scope="col">User</th>
                <th scope="col">Action</th>
                <th scope="col">Module</th>
                <th scope="col">Record</th>
                <th scope="col">Changes</th>
                <th scope="col">IP</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $log)
                <tr>
                    <td class="text-muted text-nowrap">{{ \App\Support\Format::dateTime($log->created_at) }}</td>
                    <td class="fw-semibold">{{ $log->user_name ?? $log->user?->name ?? 'System' }}</td>
                    <td>
                        @php $actionColor = ['created' => 'success', 'create' => 'success', 'updated' => 'primary', 'update' => 'primary', 'deleted' => 'danger', 'delete' => 'danger'][$log->action] ?? 'secondary'; @endphp
                        <span class="lp-badge bg-{{ $actionColor }}-subtle text-{{ $actionColor }} bg-opacity-10">{{ \App\Support\Format::titleCase($log->action) }}</span>
                    </td>
                    <td>{{ \App\Support\Format::titleCase($log->module ?? 'system') }}</td>
                    <td>
                        <div class="fw-semibold">{{ $log->record_label ?? class_basename($log->record_type ?? '') }}</div>
                        <div class="text-muted" style="font-size:.73rem">#{{ $log->record_id ?? '—' }}</div>
                    </td>
                    <td>
                        @php
                            $old = is_array($log->old_values) ? $log->old_values : (json_decode((string) $log->old_values, true) ?: []);
                            $new = is_array($log->new_values) ? $log->new_values : (json_decode((string) $log->new_values, true) ?: []);
                        @endphp
                        @if ($old || $new)
                            <button type="button" class="btn btn-sm btn-light" data-bs-toggle="collapse" data-bs-target="#audit-{{ $log->id }}">
                                <i class="bi bi-code-square"></i> {{ count($new ?: $old) }} field(s)
                            </button>
                            <div class="collapse mt-2" id="audit-{{ $log->id }}">
                                <div class="lp-audit-diff">
                                    @foreach (array_unique(array_merge(array_keys($old), array_keys($new))) as $field)
                                        <div class="lp-audit-diff__row">
                                            <span class="lp-audit-diff__field">{{ \App\Support\Format::titleCase($field) }}</span>
                                            <span class="lp-audit-diff__old">{{ \Illuminate\Support\Str::limit((string) ($old[$field] ?? '—'), 40) }}</span>
                                            <i class="bi bi-arrow-right"></i>
                                            <span class="lp-audit-diff__new">{{ \Illuminate\Support\Str::limit((string) ($new[$field] ?? '—'), 40) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <span class="text-muted">{{ $log->description ?? '—' }}</span>
                        @endif
                    </td>
                    <td class="text-muted" style="font-size:.75rem">{{ $log->ip_address ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="7"><x-empty-state icon="bi-shield-check" title="No audit entries" message="System activity is recorded automatically as users work." /></td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('partials.pagination-bar', ['items' => $items])
