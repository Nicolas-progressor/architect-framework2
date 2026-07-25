<?php

declare(strict_types=1);

namespace Blueprint\Engine\Contracts;

/**
 * Blueprint Template Engine Interface
 * 
 * @package Blueprint\Engine\Contracts
 */
interface BlueprintInterface
{
    /**
     * Render template with context
     */
    public function render(string $template, array $context = []): string;

    /**
     * Render string template
     */
    public function renderString(string $source, array $context = []): string;

    /**
     * Check if template exists
     */
    public function exists(string $template): bool;

    /**
     * Add global variable
     */
    public function addGlobal(string $key, mixed $value): self;

    /**
     * Render element/widget
     */
    public function element(string $name, array $data = []): string;

    /**
     * Clear cache
     */
    public function clearCache(): bool;
}
