@extends('layouts.app')

@section('title', 'My Profile')
@section('page-header', true)
@section('page-title', 'My Profile')
@section('page-subtitle', ($user->designation ?? 'Team member').' · '.($user->role?->name ?? 'Role pending'))

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Profile</li>
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-lg-4">
            <x-card title="Account" icon="bi-person-circle">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="lp-avatar lp-avatar--xl">{{ $user->initials() }}</span>
                    <div>
                        <div class="fw-semibold">{{ $user->name }}</div>
                        <div class="text-muted small">{{ $user->email }}</div>
                        <div class="mt-1"><x-status-badge :status="$user->status" /></div>
                    </div>
                </div>

                <div class="lp-kv"><span class="lp-kv__label">Phone</span><span class="lp-kv__value">{{ $user->phone ?? '—' }}</span></div>
                <div class="lp-kv"><span class="lp-kv__label">Role</span><span class="lp-kv__value">{{ $user->role?->name ?? '—' }}</span></div>
                <div class="lp-kv"><span class="lp-kv__label">Department</span><span class="lp-kv__value">{{ $user->employee?->department?->name ?? '—' }}</span></div>
                <div class="lp-kv"><span class="lp-kv__label">Designation</span><span class="lp-kv__value">{{ $user->employee?->designation?->name ?? $user->designation ?? '—' }}</span></div>
                <div class="lp-kv"><span class="lp-kv__label">Employee code</span><span class="lp-kv__value">{{ $user->employee?->employee_code ?? '—' }}</span></div>
                <div class="lp-kv"><span class="lp-kv__label">Member since</span><span class="lp-kv__value">{{ \App\Support\Format::date($user->created_at) }}</span></div>

                <div class="d-grid gap-2 mt-3">
                    <a href="{{ route('password.change') }}" class="btn btn-light"><i class="bi bi-key"></i> Change password</a>
                    <a href="{{ route('profile.notifications') }}" class="btn btn-light"><i class="bi bi-bell"></i> My notifications</a>
                </div>
            </x-card>
        </div>

        <div class="col-lg-8">
            <x-card title="Update profile" icon="bi-pencil-square" description="These details are shown across the workspace.">
                <form method="POST" action="{{ route('profile.update') }}" data-ajax>
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-6"><x-input name="name" label="Full name" :value="$user->name" required /></div>
                        <div class="col-md-6"><x-input name="email" label="Email" type="email" :value="$user->email" required /></div>
                        <div class="col-md-4"><x-input name="phone" label="Phone" :value="$user->phone" maxlength="10" /></div>
                        <div class="col-md-4"><x-input name="designation" label="Designation" :value="$user->designation" /></div>
                        <div class="col-md-4">
                            <label class="form-label d-block">Preferences</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" value="1" id="email_notifications"
                                       name="email_notifications" @checked($user->email_notifications ?? true)>
                                <label class="form-check-label small" for="email_notifications">Email notifications</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button class="btn btn-primary"><i class="bi bi-check2"></i> Save changes</button>
                    </div>
                </form>
            </x-card>

            <x-card title="Permissions" icon="bi-shield-lock" class="mt-4">
                <div class="fw-semibold small mb-2">From role: {{ $user->role?->name ?? '—' }}</div>
                <div class="d-flex flex-wrap gap-1 mb-3">
                    @forelse ($user->role?->permissions ?? collect() as $permission)
                        <span class="lp-badge bg-primary-subtle text-primary bg-opacity-10">{{ $permission->name }}</span>
                    @empty
                        <span class="text-muted small">No permissions mapped to this role.</span>
                    @endforelse
                </div>

                @if (($user->extraPermissions ?? collect())->isNotEmpty())
                    <div class="fw-semibold small mb-2">Additional permissions</div>
                    <div class="d-flex flex-wrap gap-1">
                        @foreach ($user->extraPermissions as $permission)
                            <span class="lp-badge bg-success-subtle text-success bg-opacity-10">{{ $permission->name }}</span>
                        @endforeach
                    </div>
                @endif
            </x-card>
        </div>
    </div>
@endsection
