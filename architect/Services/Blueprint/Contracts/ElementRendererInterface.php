<?php

declare(strict_types=1);

namespace Architect\Services\Blueprint\Contracts;

/**
 * Interface for element/widget rendering
 */
interface ElementRendererInterface
{
    /**
     * Render element by name
     */
    public function render(string $name, array $data = []): string;

    /**
     * Check if element exists
     */
    public function exists(string $name): bool;

    /**
     * Reload configuration (for context changes)
     */
    public function reload(): void;
}
