<?php

declare(strict_types=1);

namespace Architect\Helpers\NumberHelper\Facades;

use Architect\Helpers\Core\Facade;

/**
 * Facade for Number helper.
 *
 * @method static string format(float|int $number, int $decimals = 0, string $decimalSeparator = '.', string $thousandsSeparator = ',')
 * @method static string short(float|int $number, int $precision = 1)
 * @method static bool isEven(int $number)
 * @method static bool isOdd(int $number)
 * @method static bool isBetween(float|int $value, float|int $min, float|int $max)
 * @method static string toWords(int $number)
 * @method static string ordinal(int $number)
 * @method static float|int roundUp(float|int $value, int $multiple = 1)
 * @method static float|int roundDown(float|int $value, int $multiple = 1)
 */
class Helper_Number extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'number';
    }
}