<?php

declare(strict_types=1);

namespace Blueprint\Engine\Filters;

/**
 * Number Filters
 * 
 * Filters for number manipulation.
 * 
 * @package Blueprint\Engine\Filters
 */
class NumberFilters
{
    /**
     * Get all number filters
     * 
     * @return array<string, callable>
     */
    public static function getFilters(): array
    {
        return [
            'number_format' => [self::class, 'numberFormat'],
            'format' => [self::class, 'numberFormat'],
            'abs' => [self::class, 'abs'],
            'round' => [self::class, 'round'],
            'floor' => [self::class, 'floor'],
            'ceil' => [self::class, 'ceil'],
            'int' => [self::class, 'toInt'],
            'float' => [self::class, 'toFloat'],
        ];
    }

    /**
     * Format number
     */
    public static function numberFormat(mixed $value, mixed $decimals = 0, mixed $decPoint = '.', mixed $thousandsSep = ','): string
    {
        if (!is_numeric($value)) {
            return (string) $value;
        }
        return number_format((float) $value, (int) $decimals, (string) $decPoint, (string) $thousandsSep);
    }

    /**
     * Absolute value
     */
    public static function abs(mixed $value): float
    {
        return abs((float) $value);
    }

    /**
     * Round number
     */
    public static function round(mixed $value, mixed $precision = 0): float
    {
        return round((float) $value, (int) $precision);
    }

    /**
     * Floor
     */
    public static function floor(mixed $value): int
    {
        return (int) floor((float) $value);
    }

    /**
     * Ceil
     */
    public static function ceil(mixed $value): int
    {
        return (int) ceil((float) $value);
    }

    /**
     * Convert to integer
     */
    public static function toInt(mixed $value): int
    {
        return (int) $value;
    }

    /**
     * Convert to float
     */
    public static function toFloat(mixed $value): float
    {
        return (float) $value;
    }
}
