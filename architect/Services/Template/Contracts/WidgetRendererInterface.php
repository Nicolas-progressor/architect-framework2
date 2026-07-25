<?php

declare(strict_types=1);

namespace Architect\Services\Template\Contracts;

/**
 * Interface for rendering widgets.
 */
interface WidgetRendererInterface
{
    /**
     * Render widget by module and controller.
     */
    public function render(string $module, string $controller, string $action = 'create'): string;

    /**
     * Check if widget exists.
     */
    public function exists(string $module, string $controller): bool;
}
