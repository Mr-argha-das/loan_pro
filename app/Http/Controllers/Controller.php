<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

abstract class Controller
{
    use AuthorizesRequests;
    use ValidatesRequests;

    protected function perPage(Request $request, int $default = 25): int
    {
        $allowed = config('loanpro.pagination.options', [10, 25, 50, 100]);
        $requested = (int) $request->integer('per_page', $default);

        return in_array($requested, $allowed, true) ? $requested : $default;
    }

    protected function ok(string $message, array $extra = []): JsonResponse
    {
        return response()->json(array_merge(['success' => true, 'message' => $message], $extra));
    }

    protected function fail(string $message, int $status = 422, array $extra = []): JsonResponse
    {
        return response()->json(array_merge(['success' => false, 'message' => $message], $extra), $status);
    }

    protected function redirectBackWith(string $message, string $type = 'success'): RedirectResponse
    {
        return back()->with($type, $message);
    }

    /**
     * Standard payload for AJAX table refreshes.
     */
    protected function tablePayload(LengthAwarePaginator $paginator, string $view, array $extra = []): JsonResponse
    {
        return response()->json(array_merge([
            'success' => true,
            'html' => view($view, array_merge(['items' => $paginator], $extra))->render(),
            'meta' => [
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ], $extra));
    }
}
