<?php

declare(strict_types=1);

namespace Architect\Services\Template\Contracts;

/**
 * Interface for loading template configuration.
 */
interface TemplateConfigLoaderInterface
{
    /**
     * Set application directory for config loading.
     */
    public function setAppDir(string $appDir): void;

    /**
     * Load template configuration for current app.
     */
    public function load(): array;

    /**
     * Get default template name from config.
     */
    public function getDefaultTemplate(): ?string;

    /**
     * Check if template should be disabled for GET requests.
     */
    public function isNotemplateGet(): bool;
}
