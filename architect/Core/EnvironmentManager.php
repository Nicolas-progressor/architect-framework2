<?php

declare(strict_types=1);

namespace Architect\Core;

use Architect\Contracts\Core\EnvironmentInterface;
use Architect\Core\Config\ConfigInterface;
use Architect\Core\Environment\DotEnvLoader;
use Architect\Core\Environment\EnvDetectorInterface;

/**
 * Environment manager for application environment detection and configuration.
 *
 * Uses separate components for detection, .env loading, and configuration.
 */
class EnvironmentManager implements EnvironmentInterface
{
    private string $environment;
    private ConfigInterface $config;

    public function __construct(
        ?EnvDetectorInterface $envDetector = null,
        ?DotEnvLoader $dotEnvLoader = null,
        ?ConfigInterface $config = null
    ) {
        $envDetector ??= new \Architect\Core\Environment\EnvDetector();
        $dotEnvLoader ??= new DotEnvLoader();
        $this->config = $config ?? new \Architect\Core\Config\JsonConfigLoader(
            APP_DIR . 'config/',
            $envDetector->detect()
        );

        $this->environment = $envDetector->detect();
        $dotEnvLoader->load(ROOT_DIR . '.env');
    }

    public function getEnvironment(): string
    {
        return $this->environment;
    }

    public function isDevelopment(): bool
    {
        return $this->environment === 'development';
    }

    public function isTesting(): bool
    {
        return $this->environment === 'testing';
    }

    public function isStaging(): bool
    {
        return $this->environment === 'staging';
    }

    public function isProduction(): bool
    {
        return $this->environment === 'production';
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config->get($key, $default);
    }

    public function all(): array
    {
        return $this->config->all();
    }

    /**
     * Check if configuration is loaded.
     */
    public function isConfigLoaded(): bool
    {
        return $this->config->isLoaded();
    }
}
