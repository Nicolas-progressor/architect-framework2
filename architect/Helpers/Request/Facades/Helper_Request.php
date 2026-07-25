<?php

declare(strict_types=1);

namespace Architect\Helpers\Request\Facades;

use Architect\Helpers\Core\Facade;

/**
 * Facade for Request helper.
 *
 * @method static mixed get(string $name)
 * @method static mixed post(string $name)
 * @method static mixed cpu(int $number)
 * @method static array all()
 */
class Helper_Request extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'request';
    }
}