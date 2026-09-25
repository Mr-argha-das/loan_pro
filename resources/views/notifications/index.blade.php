@extends('layouts.app')

@section('title', 'Notifications')
@section('page-header', true)
@section('page-title', 'Notification Centre')
@section('page-subtitle', $unreadCount.' unread of '.$notifications->total().' notifications')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Notifications</li>
@endsection

@section('page-actions')
    <div class="d-flex gap-2">
        <form method="POST" action="{{ route('notifications.read-all') }}">
            @csrf
            <button class="btn btn-light btn-sm"><i class="bi bi-check2-all"></i> Mark all read</button>
        </form>
    </div>
@endsection

@section('content')
    <x-filter-panel :action="route('notifications.index')" :reset-url="route('notifications.index')">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label" for="q">Search</label>
                <x-search-box name="q" :value="$filters['q'] ?? null" placeholder="Title or message" />
            </div>
            <div class="col-md-3">
                <label class="form-label" for="notification_type_id">Type</label>
                <select class="form-select" id="notification_type_id" name="notification_type_id">
                    <option value="">All types</option>
                    @foreach ($types as $type)
                        <option value="{{ $type->id }}" @selected((int) ($filters['notification_type_id'] ?? 0) === $type->id)>{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="module">Module</label>
                <select class="form-select" id="module" name="module">
                    <option value="">All modules</option>
                    @foreach ($modules as $module)
                        <option value="{{ $module }}" @selected(($filters['module'] ?? null) === $module)>{{ \App\Support\Format::titleCase($module) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="status">Read state</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All</option>
                    <option value="unread" @selected(($filters['status'] ?? null) === 'unread')>Unread only</option>
                    <option value="read" @selected(($filters['status'] ?? null) === 'read')>Read only</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="per_page">Rows</label>
                <select class="form-select" id="per_page" name="per_page">
                    @foreach (config('loanpro.pagination.options', [10, 25, 50, 100]) as $option)
                        <option value="{{ $option }}" @selected((int) ($filters['per_page'] ?? 25) === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-filter-panel>

    <x-card :padding="false">
        <div id="notification-table-body">
            @include('notifications.partials.table', ['items' => $notifications])
        </div>
    </x-card>
@endsection

@push('scripts')
<script type="module">
    LoanPro.bindTableFilters({ url: '{{ route('notifications.index') }}', container: '#notification-table-body' });
</script>
@endpush
