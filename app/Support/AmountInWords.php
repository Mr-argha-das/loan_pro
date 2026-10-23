<?php

namespace App\Support;

/**
 * Converts an amount into English words using the Indian numbering system
 * (Thousand, Lakh, Crore). Used on invoice PDFs, e.g. "Ninety Thousand Two Hundred Seventy Rupees Only".
 */
class AmountInWords
{
    private const ONES = [
        'Zero', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
        'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen',
    ];

    private const TENS = [
        2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty',
        6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety',
    ];

    public static function rupees(float|int|string|null $amount): string
    {
        $amount = round((float) $amount, 2);
        $rupees = (int) floor(abs($amount));
        $paise = (int) round((abs($amount) - $rupees) * 100);

        $text = ($rupees === 0 ? 'Zero' : self::number($rupees)).' Rupees';

        if ($paise > 0) {
            $text .= ' and '.self::number($paise).' Paise';
        }

        return $text.' Only';
    }

    private static function number(int $n): string
    {
        if ($n < 100) {
            return self::underHundred($n);
        }

        $units = [[10000000, 'Crore'], [100000, 'Lakh'], [1000, 'Thousand'], [100, 'Hundred']];
        $words = [];

        foreach ($units as [$unit, $name]) {
            if ($n >= $unit) {
                $words[] = self::number(intdiv($n, $unit)).' '.$name;
                $n %= $unit;
            }
        }

        if ($n > 0) {
            $words[] = self::underHundred($n);
        }

        return implode(' ', $words);
    }

    private static function underHundred(int $n): string
    {
        if ($n < 20) {
            return self::ONES[$n];
        }

        $tens = self::TENS[intdiv($n, 10)];

        return $n % 10 === 0 ? $tens : $tens.' '.self::ONES[$n % 10];
    }
}
