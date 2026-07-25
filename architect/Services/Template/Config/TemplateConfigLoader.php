<?php

declare(strict_types=1);

namespace Architect\Services\Template\Config;

use Architect\Services\Template\Contracts\TemplateConfigLoaderInterface;
use Architect\Services\Template\Util\JsonParser;

/**
 * Loads template configuration from JSON files.
 *
 * Configuration is loaded lazily when the app directory is set.
 */
final class TemplateConfigLoader implements TemplateConfigLoaderInterface
{
    private ?array $cachedConfig = null;
    private ?string $appDir = null;

    public function setAppDir(string $appDir): void
    {
        $this->appDir = $appDir;
        $this->cachedConfig = null; // Reset cache when app changes
    }

    public function load(): array
    {
        if ($this->cachedConfig !== null) {
            return $this->cachedConfig;
        }

        if ($this->appDir === null) {
            $this->cachedConfig = [];
            return [];
        }

        $configPath = $this->appDir . 'config/template.json';
        $this->cachedConfig = JsonParser::parseFile($configPath);

        return $this->cachedConfig;
    }

    public function getDefaultTemplate(): ?string
    {
        $config = $this->load();
        return $config['template'] ?? null;
    }

    /**
     * Check if template should be disabled for GET requests.
     */
    public function isNotemplateGet(): bool
    {
        $config = $this->load();
        return $config['notemplate_get'] ?? false;
    }
}
