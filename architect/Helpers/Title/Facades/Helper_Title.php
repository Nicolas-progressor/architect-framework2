<?php

declare(strict_types=1);

namespace Architect\Helpers\Title\Facades;

use Architect\Helpers\Core\Facade;

/**
 * Facade for Title helper.
 *
 * @method static \Architect\Helpers\Title\TitleHelper set(string $title)
 * @method static \Architect\Helpers\Title\TitleHelper append(string $title)
 * @method static \Architect\Helpers\Title\TitleHelper prepend(string $title)
 * @method static string render()
 * @method static string get()
 */
class Helper_Title extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'title';
    }
}