<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Resolves an Indian postal pincode to its city (district) and state so the
 * lead wizard can auto-fill them. Results are cached; a failed lookup returns
 * found=false and the user simply types the city/state manually.
 */
class PincodeController extends Controller
{
    public function lookup(string $pincode): JsonResponse
    {
        abort_unless(preg_match('/^[1-9][0-9]{5}$/', $pincode) === 1, 422, 'Enter a valid 6 digit pincode.');

        $result = Cache::remember('pincode:'.$pincode, now()->addDays(7), function () use ($pincode) {
            try {
                $response = Http::timeout(5)->acceptJson()
                    ->get('https://api.postalpincode.in/pincode/'.$pincode);

                $office = $response->successful() ? ($response->json()[0]['PostOffice'][0] ?? null) : null;

                return $office ? [
                    'city' => $office['District'] ?? null,
                    'state' => $office['State'] ?? null,
                ] : null;
            } catch (\Throwable) {
                return null;
            }
        });

        return response()->json([
            'success' => true,
            'found' => (bool) $result,
            'city' => $result['city'] ?? null,
            'state' => $result['state'] ?? null,
        ]);
    }
}
