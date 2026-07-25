<?php

declare(strict_types=1);

namespace Architect\Helpers\Breadcrumbs\Facades;

use Architect\Helpers\Core\Facade;

/**
 * Facade for Breadcrumbs helper.
 *
 * @method static \Architect\Helpers\Breadcrumbs\BreadcrumbsHelper add(string $title, ?string $url = null, bool $active = false)
 * @method static array all()
 * @method static \Architect\Helpers\Breadcrumbs\BreadcrumbsHelper clear()
 * @method static string render()
 */
class Helper_Breadcrumbs extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'breadcrumbs';
    }
}