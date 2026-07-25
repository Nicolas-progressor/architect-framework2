<?php

declare(strict_types=1);

namespace Blueprint\Engine\Contracts;

/**
 * Function Registry Interface
 * 
 * @package Blueprint\Engine\Contracts
 */
interface FunctionRegistryInterface
{
    /**
     * Get function by name
     */
    public function get(string $name): ?callable;

    /**
     * Check if function exists
     */
    public function has(string $name): bool;

    /**
     * Register function
     */
    public function register(string $name, callable $function): void;

    /**
     * Get all functions
     */
    public function getAll(): array;
}
