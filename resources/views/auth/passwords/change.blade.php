@extends('layouts.app')

@section('title', 'Change Password')
@section('page-header', true)
@section('page-title', 'Change password')
@section('page-subtitle', 'Choose a strong password of at least 8 characters.')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('profile.show') }}">Profile</a></li>
    <li class="breadcrumb-item active" aria-current="page">Change password</li>
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-lg-6">
            <x-card title="Update password" icon="bi-key">
                <form method="POST" action="{{ route('password.update') }}" novalidate>
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label" for="current_password">Current password<span class="req">*</span></label>
                        <input type="password" class="form-control @error('current_password') is-invalid @enderror"
                               id="current_password" name="current_password" required autocomplete="current-password">
                        @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="password">New password<span class="req">*</span></label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror"
                               id="password" name="password" required autocomplete="new-password">
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="password_confirmation">Confirm new password<span class="req">*</span></label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
                    </div>

                    <div class="d-flex gap-2">
                        <a href="{{ route('profile.show') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button class="btn btn-primary"><i class="bi bi-check2"></i> Update password</button>
                    </div>
                </form>
            </x-card>
        </div>

        <div class="col-lg-6">
            <x-card title="Password policy" icon="bi-shield-check">
                <ul class="small text-muted ps-3 mb-0">
                    <li class="mb-2">Minimum 8 characters with letters and numbers.</li>
                    <li class="mb-2">Avoid reusing your previous password.</li>
                    <li>All sessions stay signed in after a change — sign out from other devices if needed.</li>
                </ul>
            </x-card>
        </div>
    </div>
@endsection
