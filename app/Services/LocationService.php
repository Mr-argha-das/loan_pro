<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;

/**
 * Turns a GPS fix into a readable place name for the attendance register.
 *
 * Resolution order:
 *   1. the office configured in Settings (within its radius) - "Head Office"
 *   2. a reverse geocode lookup (OpenStreetMap by default) - "Andheri, Mumbai"
 *   3. the closest known place from `config('geo.cities')` - "Pune, Maharashtra"
 *
 * Step 2 needs internet access, so it is skipped whenever the provider is
 * disabled or unreachable; steps 1 and 3 always work offline.
 */
class LocationService
{
    /** @var array<string, mixed> Settings read once per request. */
    protected array $settings = [];

    /**
     * Human readable name of the place the given coordinates point at.
     */
    public function name(?float $lat, ?float $lng, bool $allowGeocode = true): ?string
    {
        if ($lat === null || $lng === null) {
            return null;
        }

        if ($office = $this->officeName($lat, $lng)) {
            return $office;
        }

        if ($allowGeocode && ($geocoded = $this->reverseGeocode($lat, $lng))) {
            return $geocoded;
        }

        return $this->nearestCity($lat, $lng);
    }

    /**
     * Name of the office the fix falls into, when one is configured.
     */
    public function officeName(float $lat, float $lng): ?string
    {
        $name = $this->setting('office_location_name');
        $officeLat = $this->setting('office_latitude');
        $officeLng = $this->setting('office_longitude');

        if (! $name || ! is_numeric($officeLat) || ! is_numeric($officeLng)) {
            return null;
        }

        $radius = (float) ($this->setting('office_radius_meters') ?: 500);
        $distance = $this->distanceInMeters($lat, $lng, (float) $officeLat, (float) $officeLng);

        return $distance <= $radius ? (string) $name : null;
    }

    /**
     * Setting value, read only once per request.
     */
    protected function setting(string $key): mixed
    {
        if (! array_key_exists($key, $this->settings)) {
            $this->settings[$key] = Setting::get($key);
        }

        return $this->settings[$key];
    }

    /**
     * Reverse geocode the fix into "Area, City, State".
     */
    public function reverseGeocode(float $lat, float $lng): ?string
    {
        if (! config('loanpro.geocoding.enabled')) {
            return null;
        }

        try {
            $response = Http::timeout((int) config('loanpro.geocoding.timeout', 3))
                ->withHeaders(['User-Agent' => (string) config('loanpro.geocoding.user_agent')])
                ->get((string) config('loanpro.geocoding.endpoint'), [
                    'format' => 'jsonv2',
                    'lat' => $lat,
                    'lon' => $lng,
                    'zoom' => 12,
                    'addressdetails' => 1,
                ]);

            if (! $response->successful()) {
                return null;
            }

            $address = $response->json('address') ?: [];

            $parts = array_filter([
                $address['suburb'] ?? $address['neighbourhood'] ?? $address['village'] ?? null,
                $address['city'] ?? $address['town'] ?? $address['county'] ?? null,
                $address['state'] ?? null,
            ]);

            $name = implode(', ', array_unique($parts));

            return $name !== '' ? $name : null;
        } catch (\Throwable) {
            // No provider, no network, rate limited - the offline fallbacks take over.
            return null;
        }
    }

    /**
     * Closest known place, e.g. "Pune, Maharashtra".
     */
    public function nearestCity(float $lat, float $lng): ?string
    {
        $cities = config('geo.cities', []);

        if (! $cities) {
            return null;
        }

        $nearest = null;
        $distance = null;

        foreach ($cities as $city) {
            $candidate = $this->distanceInKm($lat, $lng, (float) $city['lat'], (float) $city['lng']);

            if ($distance === null || $candidate < $distance) {
                $distance = $candidate;
                $nearest = $city;
            }
        }

        if (! $nearest || $distance > (float) config('loanpro.geocoding.city_radius_km', 60)) {
            return null;
        }

        return trim($nearest['name'].($nearest['state'] ? ', '.$nearest['state'] : ''));
    }

    /**
     * Great circle distance between two coordinates, in kilometres.
     */
    protected function distanceInKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;

        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLng = deg2rad($lng2 - $lng1);

        $a = sin($deltaLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($deltaLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * Same distance, in metres.
     */
    protected function distanceInMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        return $this->distanceInKm($lat1, $lng1, $lat2, $lng2) * 1000;
    }
}
