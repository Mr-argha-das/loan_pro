@extends('layouts.app')

@section('title', 'My Notifications')
@section('page-header', true)
@section('page-title', 'My Notifications')
@section('page-subtitle', $notifications->whereNull('read_at')->count().' unread')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('profile.show') }}">Profile</a></li>
    <li class="breadcrumb-item active" aria-current="page">Notifications</li>
@endsection

@section('content')
    <x-card :padding="false">
        <div class="p-3">
            @include('notifications.partials.feed', ['notifications' => $notifications])
        </div>
    </x-card>
@endsection
