<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_unless($user, 403);

        foreach ($roles as $role) {
            if ($user->role?->slug === $role) {
                return $next($request);
            }
        }

        abort(403, 'You do not have the required role to access this module.');
    }
}
