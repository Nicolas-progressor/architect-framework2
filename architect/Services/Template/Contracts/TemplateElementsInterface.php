<?php

declare(strict_types=1);

namespace Architect\Services\Template\Contracts;

/**
 * Interface for template elements management.
 */
interface TemplateElementsInterface
{
    /**
     * Get all elements.
     */
    public function getElements(): array;

    /**
     * Get routed elements.
     */
    public function getRoutedElements(): array;

    /**
     * Render element by name and return string.
     */
    public function element(string $name): string;

    /**
     * Display element directly (output to buffer).
     */
    public function displayElement(string $name): void;
}
