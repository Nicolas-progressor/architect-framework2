<?php

declare(strict_types=1);

namespace Architect\Services\App\Contracts;

/**
 * Interface for Apps service.
 */
interface AppsServiceInterface
{
    /**
     * Get current application name.
     */
    public function getCurrentApp(): string;

    /**
     * Get current application directory path.
     */
    public function getAppDir(): string;

    /**
     * Get base directory for all applications.
     */
    public function getAppsBaseDir(): string;

    /**
     * Get default application name.
     */
    public function getDefaultApp(): string;

    /**
     * Get all registered applications.
     *
     * @return array<string, AppDescriptor>
     */
    public function getApps(): array;

    /**
     * Check if application exists.
     */
    public function hasApp(string $name): bool;

    /**
     * Get application descriptor by name.
     */
    public function getAppDescriptor(string $name): ?AppDescriptor;

    /**
     * Switch to another application.
     */
    public function switchApp(string $appName): void;

    /**
     * Get current application configuration.
     *
     * @return array<string, mixed>
     */
    public function getAppConfig(): array;

    /**
     * Get configuration value by key.
     */
    public function getAppConfigValue(string $key, mixed $default = null): mixed;

    /**
     * Get default route for current application.
     *
     * @return array{module: string, controller: string, action: string}
     */
    public function getDefaultRoute(): array;

    /**
     * Get application bootstrap instance.
     */
    public function getAppBootstrap(): ?AppBootstrapInterface;
}
