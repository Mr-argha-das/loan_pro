<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleAndTimezone
{
    public function handle(Request $request, Closure $next): Response
    {
        Carbon::setLocale(config('app.locale', 'en'));

        if ($timezone = config('app.timezone')) {
            date_default_timezone_set($timezone);
        }

        return $next($request);
    }
}
