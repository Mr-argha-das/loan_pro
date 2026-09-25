<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0B1F3A">
    <title>@yield('title', 'Dashboard') &middot; {{ $companyName ?? config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')

    <script>
        // Applied before paint so the sidebar never flickers when collapsed.
        if (document.cookie.includes('lp_sidebar=collapsed')) {
            document.documentElement.classList.add('lp-sidebar-collapsed-preload');
        }
    </script>
</head>
<body class="{{ request()->cookie('lp_sidebar') === 'collapsed' ? 'lp-sidebar-collapsed' : '' }}">
    <div class="lp-shell">
        @include('partials.sidebar')

        <div class="lp-backdrop" aria-hidden="true"></div>

        <div class="lp-main">
            @include('partials.topbar')

            <main class="lp-page" id="main-content">
                @hasSection('page-header')
                    <div class="lp-page__head">
                        <div>
                            <h1 class="lp-page__title">@yield('page-title')</h1>
                            @hasSection('page-subtitle')
                                <div class="lp-page__subtitle">@yield('page-subtitle')</div>
                            @endif
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            @yield('page-actions')
                        </div>
                    </div>
                @endif

                @include('partials.breadcrumb')

                @yield('content')
            </main>

            <footer class="px-4 py-3 text-muted small border-top bg-white">
                <div class="d-flex flex-wrap justify-content-between gap-2">
                    <span>&copy; {{ now()->year }} {{ $companyName ?? config('app.name') }}. All rights reserved.</span>
                    <span>LoanPro CRM v1.0</span>
                </div>
            </footer>
        </div>
    </div>

    @include('partials.flash')
    @include('partials.confirm-modal')

    <div class="lp-toast-stack" id="lp-toast-stack" aria-live="polite" aria-atomic="true"></div>

    @stack('modals')
    @stack('scripts')
</body>
</html>
