<?php

declare(strict_types=1);

namespace Blueprint\Engine\Functions;

/**
 * Math Functions
 * 
 * Functions for mathematical operations.
 * 
 * @package Blueprint\Engine\Functions
 */
class MathFunctions
{
    /**
     * Get all math functions
     * 
     * @return array<string, callable>
     */
    public static function getFunctions(): array
    {
        return [
            'min' => [self::class, 'min'],
            'max' => [self::class, 'max'],
            'rand' => [self::class, 'rand'],
            'random_int' => [self::class, 'randomInt'],
            'round' => [self::class, 'round'],
            'floor' => [self::class, 'floor'],
            'ceil' => [self::class, 'ceil'],
            'abs' => [self::class, 'abs'],
            'pow' => [self::class, 'pow'],
            'sqrt' => [self::class, 'sqrt'],
            'log' => [self::class, 'log'],
            'exp' => [self::class, 'exp'],
            'sin' => [self::class, 'sin'],
            'cos' => [self::class, 'cos'],
            'tan' => [self::class, 'tan'],
            'pi' => [self::class, 'pi'],
        ];
    }

    /**
     * Minimum
     */
    public static function min(mixed ...$values): mixed
    {
        return min($values);
    }

    /**
     * Maximum
     */
    public static function max(mixed ...$values): mixed
    {
        return max($values);
    }

    /**
     * Random number
     */
    public static function rand(int $min = 0, int $max = PHP_INT_MAX): int
    {
        return rand($min, $max);
    }

    /**
     * Random integer (cryptographically secure)
     */
    public static function randomInt(int $min, int $max): int
    {
        return random_int($min, $max);
    }

    /**
     * Round
     */
    public static function round(float $value, int $precision = 0): float
    {
        return round($value, $precision);
    }

    /**
     * Floor
     */
    public static function floor(float $value): int
    {
        return (int) floor($value);
    }

    /**
     * Ceil
     */
    public static function ceil(float $value): int
    {
        return (int) ceil($value);
    }

    /**
     * Absolute value
     */
    public static function abs(float $value): float
    {
        return abs($value);
    }

    /**
     * Power
     */
    public static function pow(float $base, float $exp): float
    {
        return pow($base, $exp);
    }

    /**
     * Square root
     */
    public static function sqrt(float $value): float
    {
        return sqrt($value);
    }

    /**
     * Natural logarithm
     */
    public static function log(float $value, ?float $base = null): float
    {
        return $base !== null ? log($value, $base) : log($value);
    }

    /**
     * Exponential
     */
    public static function exp(float $value): float
    {
        return exp($value);
    }

    /**
     * Sine
     */
    public static function sin(float $value): float
    {
        return sin($value);
    }

    /**
     * Cosine
     */
    public static function cos(float $value): float
    {
        return cos($value);
    }

    /**
     * Tangent
     */
    public static function tan(float $value): float
    {
        return tan($value);
    }

    /**
     * Pi
     */
    public static function pi(): float
    {
        return M_PI;
    }
}
