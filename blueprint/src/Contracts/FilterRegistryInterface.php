<?php

declare(strict_types=1);

namespace Blueprint\Engine\Contracts;

/**
 * Filter Registry Interface
 * 
 * @package Blueprint\Engine\Contracts
 */
interface FilterRegistryInterface
{
    /**
     * Get filter by name
     */
    public function get(string $name): ?callable;

    /**
     * Check if filter exists
     */
    public function has(string $name): bool;

    /**
     * Register filter
     */
    public function register(string $name, callable $filter): void;

    /**
     * Get all filters
     */
    public function getAll(): array;
}
