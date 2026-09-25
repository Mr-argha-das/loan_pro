@extends('layouts.app')

@section('title', 'Not found')
@section('page-header', true)
@section('page-title', 'Page not found')
@section('page-subtitle', 'The record you are looking for may have been moved or deleted.')

@section('content')
    <div class="lp-card">
        <div class="lp-card__body text-center py-5">
            <div class="lp-tone-warning rounded-circle d-inline-grid mb-3" style="width:72px;height:72px;place-items:center">
                <i class="bi bi-compass fs-2"></i>
            </div>
            <h4>404 &middot; Not found</h4>
            <p class="text-muted mb-4">We could not find the page or record you requested.</p>
            <a href="{{ route('dashboard') }}" class="btn btn-primary"><i class="bi bi-house"></i> Back to dashboard</a>
        </div>
    </div>
@endsection
