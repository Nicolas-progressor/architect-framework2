<?php

declare(strict_types=1);

namespace Architect\Helpers\Facades;

use Architect\Helpers\Core\Facade;

/**
 * Facade for Array helper.
 *
 * @method static mixed get(array $array, string $key, mixed $default = null)
 * @method static array set(array &$array, string $key, mixed $value)
 * @method static bool has(array $array, string $key)
 * @method static mixed first(array $array, ?callable $callback = null, mixed $default = null)
 * @method static mixed last(array $array, ?callable $callback = null, mixed $default = null)
 * @method static array pluck(array $array, string $value, ?string $key = null)
 * @method static array only(array $array, array $keys)
 * @method static array except(array $array, array $keys)
 * @method static array collapse(array $array)
 * @method static array dot(array $array, string $prepend = '')
 * @method static array undot(array $array)
 * @method static array wrap(mixed $value)
 */
class Helper_Arr extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'arr';
    }
}
