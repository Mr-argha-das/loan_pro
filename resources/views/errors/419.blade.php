@extends('layouts.auth')

@section('title', 'Session expired')

@section('content')
    <div class="lp-card p-4 text-center" style="max-width:420px">
        <h4 class="mb-2">Session expired</h4>
        <p class="text-muted small">Your session timed out for security reasons. Please sign in again.</p>
        <a href="{{ route('login') }}" class="btn btn-primary w-100">Sign in</a>
    </div>
@endsection
