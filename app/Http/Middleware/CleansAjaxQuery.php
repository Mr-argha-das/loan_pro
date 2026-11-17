<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cleans the query string of an AJAX table refresh.
 *
 * Older builds of the front end appended the cache buster to a URL that already
 * had a query string, so "?page=2" arrived as "?page=2?_ts=1699999999". The
 * server then read "2?_ts=1699999999" as the page number, fell back to page 1
 * and pagination looked broken.
 *
 * The stray "?_ts=..." is stripped here, so the page number and the active
 * filters survive even when the assets have not been rebuilt yet.
 */
class CleansAjaxQuery
{
    public function handle(Request $request, Closure $next): Response
    {
        $query = $request->query->all();

        if ($query) {
            $clean = [];

            foreach ($query as $key => $value) {
                $clean[$key] = is_array($value)
                    ? array_map(fn ($item) => $this->clean($item), $value)
                    : $this->clean($value);
            }

            $request->query->replace($clean);
        }

        return $next($request);
    }

    /**
     * Removes a trailing "?_ts=..." from a query value.
     */
    protected function clean(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return preg_replace('/\?_ts=\d+$/', '', $value);
    }
}
