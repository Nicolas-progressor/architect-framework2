<?php

declare(strict_types=1);

namespace Architect\Core\Contracts;

/**
 * Framework interface for application lifecycle management.
 */
interface FrameworkInterface
{
    /**
     * Get the DI container.
     */
    public function getContainer(): ContainerInterface;

    /**
     * Get the statement manager.
     */
    public function getStatement(): StatementInterface;

    /**
     * Boot a service by identifier.
     */
    public function boot(string $serviceId): void;

    /**
     * Boot multiple services.
     */
    public function bootAll(array $serviceIds): void;

    /**
     * Run the application.
     */
    public function run(): void;

    /**
     * Get a service from container.
     */
    public function get(string $id): mixed;
}
