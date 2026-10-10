<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Resolves an Indian postal pincode to its city (district) and state so the
 * lead wizard can auto-fill them. Successful lookups are cached for 7 days.
 * Failed lookups (network error, API down, unknown pincode) are NOT cached,
 * so a temporary outage never blocks a pincode for a week. The user can
 * always type city and state manually.
 */
class PincodeController extends Controller
{
    public function lookup(string $pincode): JsonResponse
    {
        abort_unless(preg_match('/^[1-9][0-9]{5}$/', $pincode) === 1, 422, 'Enter a valid 6 digit pincode.');

        $key = 'pincode:'.$pincode;
        $result = Cache::get($key);

        if ($result === null) {
            $result = $this->fetch($pincode);

            if ($result !== null) {
                Cache::put($key, $result, now()->addDays(7));
            }
        }

        return response()->json([
            'success' => true,
            'found' => $result !== null,
            'city' => $result['city'] ?? null,
            'state' => $result['state'] ?? null,
        ]);
    }

    /**
     * @return array{city: ?string, state: ?string}|null
     */
    private function fetch(string $pincode): ?array
    {
        try {
            $response = Http::timeout(5)->acceptJson()
                ->get('https://api.postalpincode.in/pincode/'.$pincode);

            if (! $response->successful()) {
                Log::warning('Pincode lookup HTTP error', ['pincode' => $pincode, 'status' => $response->status()]);

                return null;
            }

            $office = $response->json()[0]['PostOffice'][0] ?? null;

            if (! $office) {
                return null;
            }

            return [
                'city' => $office['District'] ?? null,
                'state' => $office['State'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::warning('Pincode lookup failed', ['pincode' => $pincode, 'error' => $e->getMessage()]);

            return null;
        }
    }
}
