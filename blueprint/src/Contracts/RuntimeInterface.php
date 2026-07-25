<?php

declare(strict_types=1);

namespace Blueprint\Engine\Contracts;

/**
 * Runtime Interface
 * 
 * Contract for template runtime environment.
 * 
 * @package Blueprint\Engine\Contracts
 */
interface RuntimeInterface
{
    /**
     * Escape value for HTML output
     */
    public function escape(mixed $value): string;

    /**
     * Get object/array property
     */
    public function getProperty(mixed $object, string $property): mixed;

    /**
     * Call object method
     */
    public function callMethod(mixed $object, string $method, array $args = []): mixed;

    /**
     * Apply filter to value
     */
    public function applyFilter(string $name, mixed $value, array $args = []): mixed;

    /**
     * Call function
     */
    public function callFunction(string $name, array $args = [], array $context = []): mixed;

    /**
     * Call static method on a class
     */
    public function callStaticMethod(string $class, string $method, array $args = []): mixed;

    /**
     * Register filter
     */
    public function registerFilter(string $name, callable $filter): void;

    /**
     * Register function
     */
    public function registerFunction(string $name, callable $function): void;

    /**
     * Check if value is empty
     */
    public function isEmpty(mixed $value): bool;

    /**
     * Convert value to string
     */
    public function toString(mixed $value): string;
}
