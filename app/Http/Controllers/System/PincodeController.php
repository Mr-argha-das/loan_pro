<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;

/**
 * Resolves an Indian postal pincode to its district (city) and state so the
 * lead wizard can auto-fill them. Lookups use the local dataset in
 * resources/data/india-pincodes.json (source: saravanakumargn/All-India-Pincode-Directory, MIT),
 * so there is no external API call and no network dependency at runtime.
 */
class PincodeController extends Controller
{
    /** @var array<string, array{0: string, 1: string}>|null */
    private static ?array $dataset = null;

    public function lookup(string $pincode): JsonResponse
    {
        abort_unless(preg_match('/^[1-9][0-9]{5}$/', $pincode) === 1, 422, 'Enter a valid 6 digit pincode.');

        $entry = self::dataset()[$pincode] ?? null;

        return response()->json([
            'success' => true,
            'found' => $entry !== null,
            'city' => $entry[0] ?? null,
            'state' => $entry[1] ?? null,
        ]);
    }

    /** @return array<string, array{0: string, 1: string}> */
    private static function dataset(): array
    {
        return self::$dataset ??= File::json(resource_path('data/india-pincodes.json')) ?? [];
    }
}
