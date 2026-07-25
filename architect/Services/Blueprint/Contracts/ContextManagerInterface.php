<?php

declare(strict_types=1);

namespace Architect\Services\Blueprint\Contracts;

/**
 * Interface for managing template context (app, template, module)
 */
interface ContextManagerInterface
{
    /**
     * Set application context
     */
    public function setContext(string $appDir, ?string $templateName = null): void;

    /**
     * Set module context for view resolution
     */
    public function setModuleContext(string $modulePath): void;

    /**
     * Get current app directory
     */
    public function getCurrentAppDir(): ?string;

    /**
     * Get current template name
     */
    public function getCurrentTemplate(): ?string;

    /**
     * Get resolved template paths
     */
    public function getPaths(): array;
}
