<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Number;

class Format
{
    /** Indian digit grouping: 1234567 -> 12,34,567 */
    public static function inr(float|int|string|null $value, bool $decimals = false): string
    {
        $amount = (float) ($value ?? 0);
        $negative = $amount < 0;
        $amount = abs($amount);

        $formatted = number_format($amount, $decimals ? 2 : 0, '.', '');
        [$whole, $fraction] = array_pad(explode('.', $formatted), 2, null);

        if (strlen($whole) > 3) {
            $last3 = substr($whole, -3);
            $rest = substr($whole, 0, -3);
            $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
            $whole = $rest.','.$last3;
        }

        $out = ($negative ? '-' : '').$whole.($fraction !== null ? '.'.$fraction : '');

        return $out;
    }

    /** ₹ prefix variant used across the dashboard and tables. */
    public static function money(float|int|string|null $value, bool $decimals = false): string
    {
        return '₹'.self::inr($value, $decimals);
    }

    /** Compact business notation: 28,400,000 -> ₹2.84 Cr */
    public static function compactInr(float|int|string|null $value): string
    {
        $amount = (float) ($value ?? 0);
        $abs = abs($amount);

        return match (true) {
            $abs >= 1_00_00_000 => '₹'.rtrim(rtrim(number_format($amount / 1_00_00_000, 2, '.', ''), '0'), '.').' Cr',
            $abs >= 1_00_000 => '₹'.rtrim(rtrim(number_format($amount / 1_00_000, 2, '.', ''), '0'), '.').' L',
            $abs >= 1_000 => '₹'.rtrim(rtrim(number_format($amount / 1_000, 1, '.', ''), '0'), '.').'K',
            default => self::money($amount),
        };
    }

    public static function date(Carbon|string|null $value, string $format = 'd M Y'): string
    {
        if (! $value) {
            return '—';
        }

        return Carbon::parse($value)->format($format);
    }

    public static function dateTime(Carbon|string|null $value, string $format = 'd M Y, h:i A'): string
    {
        if (! $value) {
            return '—';
        }

        return Carbon::parse($value)->format($format);
    }

    public static function time(Carbon|string|null $value): string
    {
        return $value ? Carbon::parse($value)->format('h:i A') : '—';
    }

    public static function humanize(Carbon|string|null $value): string
    {
        return $value ? Carbon::parse($value)->diffForHumans() : '—';
    }

    public static function percent(float|int|string|null $value, int $decimals = 2): string
    {
        return rtrim(rtrim(number_format((float) ($value ?? 0), $decimals, '.', ''), '0'), '.').'%';
    }

    public static function mask(string|null $value, int $visible = 4): string
    {
        if (! $value) {
            return '—';
        }

        $len = strlen($value);

        if ($len <= $visible) {
            return str_repeat('X', $len);
        }

        return str_repeat('X', $len - $visible).substr($value, -$visible);
    }

    public static function titleCase(?string $value): string
    {
        return $value ? str_replace(['-', '_'], ' ', ucwords($value, '-_')) : '—';
    }

    public static function tenure(?int $months): string
    {
        if (! $months) {
            return '—';
        }

        $years = intdiv($months, 12);
        $rest = $months % 12;
        $parts = [];

        if ($years) {
            $parts[] = $years.' '.($years === 1 ? 'Year' : 'Years');
        }

        if ($rest) {
            $parts[] = $rest.' '.($rest === 1 ? 'Month' : 'Months');
        }

        return implode(' ', $parts);
    }

    public static function bytes(int $bytes): string
    {
        return Number::fileSize($bytes, precision: 1);
    }
}
