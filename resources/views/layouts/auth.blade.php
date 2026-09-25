<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sign in') &middot; {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body style="background:linear-gradient(135deg,#0B1F3A 0%,#123A6B 55%,#1677FF 130%);min-height:100vh">
    <main class="d-flex align-items-center justify-content-center min-vh-100 p-3">
        @yield('content')
    </main>

    <div class="lp-toast-stack" id="lp-toast-stack" aria-live="polite"></div>
    @include('partials.flash')
</body>
</html>
