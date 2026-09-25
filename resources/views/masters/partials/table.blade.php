@php
    $items = $items ?? collect();
    $columns = $masterConfig['columns'] ?? [];
    $canManage = auth()->user()->hasPermissionTo('masters.manage');
@endphp

<div class="lp-table-wrap">
    <table class="lp-table">
        <thead>
            <tr>
                @foreach ($columns as $column => $label)
                    <x-sortable-th :column="$column" :label="$label" />
                @endforeach
                <th scope="col">Status</th>
                @if ($canManage)<th scope="col" class="text-end">Actions</th>@endif
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $item)
                <tr>
                    @foreach (array_keys($columns) as $column)
                        <td @class(['fw-semibold' => $loop->first])>
                            @php $value = data_get($item, $column); @endphp
                            @if (is_bool($value))
                                {{ $value ? 'Yes' : 'No' }}
                            @elseif ($value === null || $value === '')
                                —
                            @else
                                {{ \Illuminate\Support\Str::limit((string) $value, 70) }}
                            @endif
                        </td>
                    @endforeach
                    <td>
                        @if (method_exists($item, 'trashed') && $item->trashed())
                            <span class="lp-badge bg-danger-subtle text-danger bg-opacity-10">Deleted</span>
                        @elseif (isset($item->is_active))
                            <x-status-badge :status="$item->is_active ? 'active' : 'inactive'" />
                        @else
                            —
                        @endif
                    </td>
                    @if ($canManage)
                        <td class="text-end">
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#master-edit-{{ $item->id }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                @if (isset($item->is_active) && ! ($item->trashed() ?? false))
                                    <form method="POST" action="{{ route('masters.toggle', [$masterKey, $item->id]) }}" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-light" title="Toggle status">
                                            <i class="bi bi-{{ $item->is_active ? 'pause' : 'play' }}-fill"></i>
                                        </button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('masters.destroy', [$masterKey, $item->id]) }}" class="d-inline"
                                      data-confirm="Delete this {{ strtolower($masterConfig['singular']) }}? Soft deleted records can be restored from the database.">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-light text-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) + ($canManage ? 2 : 1) }}">
                        <x-empty-state icon="{{ $masterConfig['icon'] }}" title="No {{ strtolower($masterConfig['label']) }} yet"
                                       message="Add the first record to make it available across the platform." />
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('partials.pagination-bar', ['items' => $items])

@if ($canManage)
    @foreach ($items as $item)
        <x-modal id="master-edit-{{ $item->id }}" :title="'Edit '.$masterConfig['singular']"
                 :action="route('masters.update', [$masterKey, $item->id])" method="PUT" submit-label="Update">
            <div class="row g-3">
                <div class="col-md-6"><x-input name="name" label="Name" :value="$item->name" required /></div>
                @foreach ($columns as $column => $label)
                    @continue($column === 'name')
                    <div class="col-md-6"><x-input name="{{ $column }}" :label="$label" :value="data_get($item, $column)" /></div>
                @endforeach
                @if (isset($item->is_active))
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="active-{{ $item->id }}" name="is_active" @checked($item->is_active)>
                            <label class="form-check-label" for="active-{{ $item->id }}">Active</label>
                        </div>
                    </div>
                @endif
            </div>
        </x-modal>
    @endforeach
@endif
