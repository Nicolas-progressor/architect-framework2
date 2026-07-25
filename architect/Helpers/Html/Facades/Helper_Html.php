<?php

declare(strict_types=1);

namespace Architect\Helpers\Html\Facades;

use Architect\Helpers\Core\Facade;

/**
 * Facade for Html helper.
 *
 * @method static string href(string $path)
 * @method static string|false active(string $path)
 * @method static string style(string $path, array $options = [])
 * @method static string script(string $path, array $options = [])
 * @method static string img(string $src, array $options = [])
 * @method static string icon(string $name, array $options = [])
 * @method static string tag(string $tag, string $content = '', array $options = [])
 * @method static string form(string $method, string $action = '', array $options = [])
 * @method static string input(string $type, string $name, string $value = '', array $options = [])
 * @method static string submit(string $text, array $options = [])
 */
class Helper_Html extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'html';
    }
}