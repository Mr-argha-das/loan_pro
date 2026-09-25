@extends('layouts.app')

@section('title', 'Access denied')
@section('page-header', true)
@section('page-title', 'Access denied')
@section('page-subtitle', 'Your role does not include permission for this action.')

@section('content')
    <div class="lp-card">
        <div class="lp-card__body text-center py-5">
            <div class="lp-tone-danger rounded-circle d-inline-grid mb-3" style="width:72px;height:72px;place-items:center">
                <i class="bi bi-shield-lock fs-2"></i>
            </div>
            <h4>403 &middot; Forbidden</h4>
            <p class="text-muted mb-4">{{ $message ?? 'You do not have permission to view this page.' }}</p>
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}" class="btn btn-primary">
                <i class="bi bi-arrow-left"></i> Go back
            </a>
        </div>
    </div>
@endsection
