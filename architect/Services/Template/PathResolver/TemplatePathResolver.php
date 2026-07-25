<?php

declare(strict_types=1);

namespace Architect\Services\Template\PathResolver;

use Architect\Services\App\Contracts\AppsServiceInterface;
use Architect\Services\Template\Contracts\PathResolverInterface;

/**
 * Resolves template paths with priority: app -> global.
 */
final class TemplatePathResolver implements PathResolverInterface
{
    private const TEMPLATE_DIR = 'template/';

    public function __construct(
        private readonly AppsServiceInterface $apps
    ) {}

    public function resolveTemplatePath(string $name): ?string
    {
        // 1. App template (priority)
        $appPath = $this->buildAppPath($name);
        if ($this->pathExists($appPath)) {
            return $appPath;
        }

        // 2. Global template (fallback)
        $globalPath = $this->buildGlobalPath($name);
        if ($this->pathExists($globalPath)) {
            return $globalPath;
        }

        return null;
    }

    public function resolveTemplatePathFromApp(string $appName, string $templateName): ?string
    {
        $path = ROOT_DIR . 'app/apps/' . $appName . '/' . self::TEMPLATE_DIR . $templateName . '/';

        return $this->pathExists($path) ? $path : null;
    }

    public function getBlueprintPaths(string $templatePath, string $templateName): array
    {
        $paths = [];
        $appDir = $this->apps->getAppDir();

        // Build candidates in priority order (most specific first)
        $candidates = $this->buildPathCandidates($appDir, $templateName);

        foreach ($candidates as $path) {
            if ($this->pathExists($path)) {
                $paths[] = $path;
            }
        }

        return array_unique($paths);
    }

    /**
     * Build path candidates for Blueprint search.
     */
    private function buildPathCandidates(?string $appDir, string $templateName): array
    {
        $candidates = [];

        // App-specific paths
        if ($appDir) {
            $candidates[] = $appDir . self::TEMPLATE_DIR . $templateName . '/layouts/';
            $candidates[] = $appDir . self::TEMPLATE_DIR . $templateName . '/elements/';
            $candidates[] = $appDir . self::TEMPLATE_DIR . 'layouts/';
            $candidates[] = $appDir . self::TEMPLATE_DIR . 'elements/';
            $candidates[] = $appDir . self::TEMPLATE_DIR;
        }

        // Global paths
        $globalBase = ROOT_DIR . 'app/' . self::TEMPLATE_DIR;
        $candidates[] = $globalBase . 'layouts/';
        $candidates[] = $globalBase . 'elements/';
        $candidates[] = $globalBase;

        return $candidates;
    }

    private function buildGlobalPath(string $name): string
    {
        return ROOT_DIR . 'app/' . self::TEMPLATE_DIR . $name . '/';
    }

    private function buildAppPath(string $name): string
    {
        $appDir = $this->apps->getAppDir();
        return $appDir . self::TEMPLATE_DIR . $name . '/';
    }

    private function pathExists(string $path): bool
    {
        return is_dir($path);
    }
}
