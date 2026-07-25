<?php

declare(strict_types=1);

namespace Architect\Services\Config\Contracts;

/**
 * Interface for configuration loaders.
 */
interface ConfigLoaderInterface
{
    /**
     * Load configuration by name.
     * 
     * @param string $name Configuration name (e.g., 'apps', 'template')
     * @param string|null $appPath Optional application path for lookup
     * @return ConfigInterface
     */
    public function load(string $name, ?string $appPath = null): ConfigInterface;
}
