<?php

declare(strict_types=1);

namespace Architect\Services\Session;

use Architect\Services\Session\Contracts\SessionDriverInterface;
use Architect\Services\Session\Drivers\ArraySessionDriver;
use Architect\Services\Session\Drivers\CookieSessionDriver;
use Architect\Services\Session\Drivers\FileSessionDriver;

/**
 * Session Manager — manages session drivers and provides unified API.
 */
class SessionManager
{
    /** @var array<string, SessionDriverInterface> Resolved driver instances */
    private array $drivers = [];

    /** @var string Default driver name */
    private string $defaultDriver = 'file';

    /** @var array<string, array> Driver configurations */
    private array $config = [];

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->defaultDriver = $config['default'] ?? 'file';
    }

    /**
     * Get a session driver by name.
     */
    public function driver(?string $name = null): SessionDriverInterface
    {
        $name ??= $this->defaultDriver;

        if (!isset($this->drivers[$name])) {
            $this->drivers[$name] = $this->createDriver($name);
        }

        return $this->drivers[$name];
    }

    /**
     * Register a custom driver instance.
     */
    public function extend(string $name, SessionDriverInterface $driver): void
    {
        $this->drivers[$name] = $driver;
    }

    /**
     * Register a driver via callback.
     *
     * @param callable(): SessionDriverInterface $callback
     */
    public function extendWith(string $name, callable $callback): void
    {
        $this->drivers[$name] = $callback();
    }

    /**
     * Get the default driver name.
     */
    public function getDefaultDriver(): string
    {
        return $this->defaultDriver;
    }

    /**
     * Set the default driver name.
     */
    public function setDefaultDriver(string $name): void
    {
        $this->defaultDriver = $name;
    }

    /**
     * Get all resolved driver names.
     *
     * @return array<int, string>
     */
    public function getDrivers(): array
    {
        return array_keys($this->drivers);
    }

    /**
     * Create a driver by name.
     */
    private function createDriver(string $name): SessionDriverInterface
    {
        $driverConfig = $this->config[$name] ?? [];

        return match ($name) {
            'file' => $this->createFileDriver($driverConfig),
            'cookie' => $this->createCookieDriver($driverConfig),
            'array' => $this->createArrayDriver($driverConfig),
            default => throw new \InvalidArgumentException("Session driver '{$name}' is not supported."),
        };
    }

    private function createFileDriver(array $config): FileSessionDriver
    {
        return new FileSessionDriver(
            storagePath: $config['storage_path'] ?? sys_get_temp_dir(),
            lifetime: $config['lifetime'] ?? null,
        );
    }

    private function createCookieDriver(array $config): CookieSessionDriver
    {
        return new CookieSessionDriver(
            secret: $config['secret'] ?? '',
            lifetime: $config['lifetime'] ?? null,
        );
    }

    private function createArrayDriver(array $config): ArraySessionDriver
    {
        return new ArraySessionDriver();
    }
}
