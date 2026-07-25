<?php

declare(strict_types=1);

namespace Architect\Helpers\NumberHelper;

use Architect\Helpers\Core\AbstractHelper;

/**
 * Number helper functions.
 */
class NumberHelper extends AbstractHelper
{
    /**
     * Format a number with thousands separator and decimal points.
     */
    public static function format(float|int $number, int $decimals = 0, string $decimalSeparator = '.', string $thousandsSeparator = ','): string
    {
        return number_format($number, $decimals, $decimalSeparator, $thousandsSeparator);
    }

    /**
     * Format a number as a short human-readable string (e.g., 1K, 2.5M).
     */
    public static function short(float|int $number, int $precision = 1): string
    {
        if ($number < 1000) {
            return (string) $number;
        }

        $units = ['K', 'M', 'B', 'T'];
        $unitIndex = -1;
        $value = $number;

        while ($value >= 1000 && $unitIndex < count($units) - 1) {
            $value /= 1000;
            $unitIndex++;
        }

        return round($value, $precision) . $units[$unitIndex];
    }

    /**
     * Check if a number is even.
     */
    public static function isEven(int $number): bool
    {
        return $number % 2 === 0;
    }

    /**
     * Check if a number is odd.
     */
    public static function isOdd(int $number): bool
    {
        return $number % 2 !== 0;
    }

    /**
     * Check if a number is between two values (inclusive).
     */
    public static function isBetween(float|int $value, float|int $min, float|int $max): bool
    {
        return $value >= $min && $value <= $max;
    }

    /**
     * Convert a number to words (basic English).
     */
    public static function toWords(int $number): string
    {
        static $words = [
            0 => 'zero',
            1 => 'one',
            2 => 'two',
            3 => 'three',
            4 => 'four',
            5 => 'five',
            6 => 'six',
            7 => 'seven',
            8 => 'eight',
            9 => 'nine',
            10 => 'ten',
            11 => 'eleven',
            12 => 'twelve',
            13 => 'thirteen',
            14 => 'fourteen',
            15 => 'fifteen',
            16 => 'sixteen',
            17 => 'seventeen',
            18 => 'eighteen',
            19 => 'nineteen',
            20 => 'twenty',
            30 => 'thirty',
            40 => 'forty',
            50 => 'fifty',
            60 => 'sixty',
            70 => 'seventy',
            80 => 'eighty',
            90 => 'ninety',
            100 => 'hundred',
            1000 => 'thousand',
            1000000 => 'million',
            1000000000 => 'billion',
        ];

        if (isset($words[$number])) {
            return $words[$number];
        }

        // Simple implementation for numbers up to 999
        if ($number < 100) {
            $tens = floor($number / 10) * 10;
            $units = $number % 10;
            return $words[$tens] . ($units ? '-' . $words[$units] : '');
        }

        // For larger numbers, just return the number as string
        return (string) $number;
    }

    /**
     * Convert a number to ordinal (1st, 2nd, 3rd, etc.).
     */
    public static function ordinal(int $number): string
    {
        $suffixes = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];
        $mod100 = $number % 100;
        if ($mod100 >= 11 && $mod100 <= 13) {
            $suffix = 'th';
        } else {
            $suffix = $suffixes[$number % 10];
        }
        return $number . $suffix;
    }

    /**
     * Round up to the nearest multiple of a given value.
     */
    public static function roundUp(float|int $value, int $multiple = 1): float|int
    {
        if ($multiple === 0) {
            return $value;
        }
        return ceil($value / $multiple) * $multiple;
    }

    /**
     * Round down to the nearest multiple of a given value.
     */
    public static function roundDown(float|int $value, int $multiple = 1): float|int
    {
        if ($multiple === 0) {
            return $value;
        }
        return floor($value / $multiple) * $multiple;
    }
}
