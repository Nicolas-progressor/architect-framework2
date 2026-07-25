<?php

declare(strict_types=1);

namespace Architect\Helpers\Facades;

use Architect\Helpers\Core\Facade;

/**
 * Facade for Assets helper.
 *
 * @method static \Architect\Helpers\Assets\AssetsHelper css(string $file)
 * @method static \Architect\Helpers\Assets\AssetsHelper js(string $file)
 * @method static string url(string $path)
 * @method static string img(string $src, string $alt = '')
 * @method static array getCss()
 * @method static array getJs()
 * @method static \Architect\Helpers\Assets\AssetsHelper clear()
 * @method static string renderCss()
 * @method static string renderJs()
 */
class Helper_Assets extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'assets';
    }
}