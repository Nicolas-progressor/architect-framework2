<?php

declare(strict_types=1);

namespace Architect\Services\Template\Contracts;

/**
 * Interface for template path resolution.
 */
interface PathResolverInterface
{
    /**
     * Resolve template path by name.
     * Searches in global templates first, then in app templates.
     */
    public function resolveTemplatePath(string $name): ?string;

    /**
     * Resolve template path from specific app.
     */
    public function resolveTemplatePathFromApp(string $appName, string $templateName): ?string;

    /**
     * Get all paths for Blueprint template search.
     * Returns paths in priority order (most specific first).
     */
    public function getBlueprintPaths(string $templatePath, string $templateName): array;
}
