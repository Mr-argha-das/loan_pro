<?php

namespace App\Services;

use App\Models\Lead;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Development-grade OTP service.
 *
 * In sandbox / local environments no SMS gateway is configured, so the code is
 * stored in the cache and surfaced through the lead record (and the log) - the
 * production implementation would swap `dispatch()` for the provider SDK.
 */
class OtpService
{
    public const TTL_SECONDS = 120;

    public function generate(Lead $lead): string
    {
        $otp = app()->environment('production') ? (string) random_int(100000, 999999) : '123456';

        Cache::put($this->cacheKey($lead), $otp, self::TTL_SECONDS);

        $lead->forceFill([
            'otp_channel' => 'sms',
            'otp_attempts' => 0,
        ])->save();

        $this->dispatch($lead, $otp);

        return $otp;
    }

    public function verify(Lead $lead, string $code): bool
    {
        $expected = Cache::get($this->cacheKey($lead));

        if (! $expected || $expected !== trim($code)) {
            $lead->increment('otp_attempts');

            return false;
        }

        Cache::forget($this->cacheKey($lead));

        $lead->forceFill([
            'is_otp_verified' => true,
            'otp_verified_at' => now(),
            'otp_attempts' => 0,
        ])->save();

        return true;
    }

    public function expiresIn(Lead $lead): int
    {
        return Cache::get($this->cacheKey($lead)) ? self::TTL_SECONDS : 0;
    }

    public function isVerified(Lead $lead): bool
    {
        return (bool) $lead->is_otp_verified;
    }

    protected function dispatch(Lead $lead, string $otp): void
    {
        Log::info('OTP dispatched', [
            'lead' => $lead->lead_code,
            'mobile' => $lead->customer?->mobile,
            'otp' => $otp,
        ]);
    }

    protected function cacheKey(Lead $lead): string
    {
        return 'lead_otp_'.$lead->getKey();
    }
}
