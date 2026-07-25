<?php

declare(strict_types=1);

namespace Architect\Services\Blueprint\Contracts;

/**
 * Interface for template filter registration
 */
interface FilterRegistryInterface
{
    /**
     * Register all filters with the given registrar
     */
    public function register(callable $registrar): void;

    /**
     * Get filter definitions
     * @return array<string, callable>
     */
    public function getFilters(): array;
}
