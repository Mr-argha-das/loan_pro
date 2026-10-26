<?php

namespace App\Support;

/**
 * Offline GSTIN helpers: format and check-digit validation, PAN and state derivation.
 * No network call is needed for these; they are always available.
 */
final class GstinDetails
{
    /** GST state codes (first two digits of a GSTIN). */
    public const STATES = [
        '01' => 'Jammu and Kashmir', '02' => 'Himachal Pradesh', '03' => 'Punjab', '04' => 'Chandigarh',
        '05' => 'Uttarakhand', '06' => 'Haryana', '07' => 'Delhi', '08' => 'Rajasthan',
        '09' => 'Uttar Pradesh', '10' => 'Bihar', '11' => 'Sikkim', '12' => 'Arunachal Pradesh',
        '13' => 'Nagaland', '14' => 'Manipur', '15' => 'Mizoram', '16' => 'Tripura', '17' => 'Meghalaya',
        '18' => 'Assam', '19' => 'West Bengal', '20' => 'Jharkhand', '21' => 'Odisha', '22' => 'Chhattisgarh',
        '23' => 'Madhya Pradesh', '24' => 'Gujarat', '26' => 'Dadra and Nagar Haveli and Daman and Diu',
        '27' => 'Maharashtra', '29' => 'Karnataka', '30' => 'Goa', '31' => 'Lakshadweep', '32' => 'Kerala',
        '33' => 'Tamil Nadu', '34' => 'Puducherry', '35' => 'Andaman and Nicobar Islands', '36' => 'Telangana',
        '37' => 'Andhra Pradesh', '38' => 'Ladakh', '97' => 'Other Territory',
    ];

    public static function normalize(string $gstin): string
    {
        return strtoupper(trim($gstin));
    }

    /** Format plus check digit (last character) must both be valid. */
    public static function isValid(string $gstin): bool
    {
        $gstin = self::normalize($gstin);

        if (preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/', $gstin) !== 1) {
            return false;
        }

        return self::checkDigit(substr($gstin, 0, 14)) === substr($gstin, 14, 1);
    }

    /** PAN is characters 3 to 12 of the GSTIN. */
    public static function pan(string $gstin): ?string
    {
        $gstin = self::normalize($gstin);

        return strlen($gstin) === 15 ? substr($gstin, 2, 10) : null;
    }

    /** Place of supply in the usual "09-Uttar Pradesh" form. */
    public static function placeOfSupply(string $gstin): ?string
    {
        $code = substr(self::normalize($gstin), 0, 2);

        return isset(self::STATES[$code]) ? $code.'-'.self::STATES[$code] : null;
    }

    private static function checkDigit(string $first14): string
    {
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $sum = 0;

        for ($i = 0; $i < 14; $i++) {
            $value = strpos($chars, $first14[$i]);
            $product = $value * (($i % 2) + 1);
            $sum += intdiv($product, 36) + ($product % 36);
        }

        return $chars[(36 - ($sum % 36)) % 36];
    }
}
