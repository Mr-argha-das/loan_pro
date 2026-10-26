<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Support\GstinDetails;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Invoice form helper: given a GSTIN, returns PAN and place of supply (offline) and,
 * when GSTIN_API_KEY is configured, the registered legal name, trade name and address.
 * Provider responses are cached for 30 days; failures are not cached.
 */
class GstinLookupController extends Controller
{
    public function lookup(string $gstin): JsonResponse
    {
        $gstin = GstinDetails::normalize($gstin);

        if (! GstinDetails::isValid($gstin)) {
            return response()->json([
                'success' => false,
                'message' => 'Enter a valid 15 character GSTIN.',
            ], 422);
        }

        $data = [
            'gstin' => $gstin,
            'pan' => GstinDetails::pan($gstin),
            'place_of_supply' => GstinDetails::placeOfSupply($gstin),
            'legal_name' => null,
            'trade_name' => null,
            'address' => null,
            'status' => null,
        ];

        $key = (string) config('services.gstin.key');
        $message = null;

        if ($key === '') {
            $message = 'Legal name and address lookup is not configured (GSTIN_API_KEY missing). PAN and place of supply were filled from the GSTIN.';
        } else {
            $remote = $this->fetchRemote($gstin, $key);

            if ($remote === null) {
                $message = 'GST details could not be fetched right now. PAN and place of supply were filled from the GSTIN.';
            } else {
                $data = array_merge($data, $remote);
            }
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ]);
    }

    /** @return array{legal_name: ?string, trade_name: ?string, address: ?string, status: ?string}|null */
    private function fetchRemote(string $gstin, string $key): ?array
    {
        $cached = Cache::get('gstin:'.$gstin);

        if (is_array($cached)) {
            return $cached;
        }

        try {
            $response = Http::withHeaders(['x-api-key' => $key, 'Accept' => 'application/json'])
                ->timeout(8)
                ->get(rtrim((string) config('services.gstin.base_url'), '/').'/v1/gstin/'.$gstin);
        } catch (\Throwable $e) {
            Log::warning('GSTIN lookup failed', ['gstin' => $gstin, 'error' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('GSTIN lookup returned an error', ['gstin' => $gstin, 'status' => $response->status()]);

            return null;
        }

        $json = $response->json();
        $legal = $json['legal_name'] ?? null;

        if (blank($legal)) {
            return null;
        }

        $address = collect([$json['address'] ?? null, $json['pincode'] ?? null])
            ->filter(fn ($part) => filled($part))
            ->implode(', ');

        $result = [
            'legal_name' => $legal,
            'trade_name' => $json['trade_name'] ?? null,
            'address' => $address !== '' ? $address : null,
            'status' => $json['status'] ?? null,
        ];

        Cache::put('gstin:'.$gstin, $result, now()->addDays(30));

        return $result;
    }
}
