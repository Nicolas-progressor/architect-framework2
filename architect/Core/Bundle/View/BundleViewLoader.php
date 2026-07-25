<?php

declare(strict_types=1);

namespace Architect\Core\Bundle\View;

use Architect\Contracts\BundleInterface;

/**
 * Loads views and templates from bundles.
 */
class BundleViewLoader
{
    /**
     * Get view directories for a bundle.
     *
     * @param BundleInterface $bundle
     * @return string[]
     */
    public function getViewDirectories(BundleInterface $bundle): array
    {
        $reflection = new \ReflectionClass($bundle);
        $bundleDir = dirname($reflection->getFileName());

        $directories = [];

        // Check for Resources/views directory
        $viewsDir = $bundleDir . '/Resources/views';
        if (is_dir($viewsDir)) {
            $directories[] = $viewsDir;
        }

        // Check for views directory
        $viewsDir = $bundleDir . '/views';
        if (is_dir($viewsDir)) {
            $directories[] = $viewsDir;
        }

        // Check for View directory
        $viewsDir = $bundleDir . '/View';
        if (is_dir($viewsDir)) {
            $directories[] = $viewsDir;
        }

        // Check for Resources/templates directory
        $templatesDir = $bundleDir . '/Resources/templates';
        if (is_dir($templatesDir)) {
            $directories[] = $templatesDir;
        }

        // Check for templates directory
        $templatesDir = $bundleDir . '/templates';
        if (is_dir($templatesDir)) {
            $directories[] = $templatesDir;
        }

        // Check for Template directory
        $templatesDir = $bundleDir . '/Template';
        if (is_dir($templatesDir)) {
            $directories[] = $templatesDir;
        }

        return $directories;
    }

    /**
     * Get template directories for a bundle.
     *
     * @param BundleInterface $bundle
     * @return string[]
     */
    public function getTemplateDirectories(BundleInterface $bundle): array
    {
        $reflection = new \ReflectionClass($bundle);
        $bundleDir = dirname($reflection->getFileName());

        $directories = [];

        // Check for Resources/templates directory
        $templatesDir = $bundleDir . '/Resources/templates';
        if (is_dir($templatesDir)) {
            $directories[] = $templatesDir;
        }

        // Check for templates directory
        $templatesDir = $bundleDir . '/templates';
        if (is_dir($templatesDir)) {
            $directories[] = $templatesDir;
        }

        // Check for Template directory
        $templatesDir = $bundleDir . '/Template';
        if (is_dir($templatesDir)) {
            $directories[] = $templatesDir;
        }

        return $directories;
    }

    /**
     * Register bundle views in the template system.
     *
     * @param BundleInterface $bundle
     * @param mixed $templateService
     */
    public function registerViews(BundleInterface $bundle, $templateService): void
    {
        $viewDirs = $this->getViewDirectories($bundle);
        $bundleName = $bundle->getName();

        foreach ($viewDirs as $viewDir) {
            $this->registerViewDirectory($bundleName, $viewDir, $templateService);
        }
    }

    /**
     * Register a view directory in the template system.
     *
     * @param string $bundleName
     * @param string $viewDir
     * @param mixed $templateService
     */
    private function registerViewDirectory(string $bundleName, string $viewDir, $templateService): void
    {
        // Check if template service has addPath method (like Blueprint)
        if (method_exists($templateService, 'addPath')) {
            $templateService->addPath($viewDir, $bundleName);
        }

        // Check if template service has addNamespace method
        if (method_exists($templateService, 'addNamespace')) {
            $templateService->addNamespace($bundleName, $viewDir);
        }

        // For Architect's template system, we might need to register in config
        $this->registerInConfig($bundleName, $viewDir);
    }

    /**
     * Register bundle views in configuration.
     *
     * @param string $bundleName
     * @param string $viewDir
     */
    private function registerInConfig(string $bundleName, string $viewDir): void
    {
        $configFile = ROOT_DIR . 'app/config/bundles/views.json';
        $configDir = dirname($configFile);

        if (!is_dir($configDir)) {
            mkdir($configDir, 0o755, true);
        }

        $config = [];
        if (file_exists($configFile)) {
            $content = file_get_contents($configFile);
            if ($content !== false) {
                $config = json_decode($content, true) ?: [];
            }
        }

        $config[$bundleName] = $viewDir;

        file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
    }

    /**
     * Register views for all bundles.
     *
     * @param array $bundles
     * @param mixed $templateService
     */
    public function registerAllViews(array $bundles, $templateService): void
    {
        foreach ($bundles as $bundle) {
            $this->registerViews($bundle, $templateService);
        }
    }

    /**
     * Get view path for a bundle template.
     *
     * @param BundleInterface $bundle
     * @param string $template
     * @return string|null
     */
    public function getViewPath(BundleInterface $bundle, string $template): ?string
    {
        $viewDirs = $this->getViewDirectories($bundle);

        foreach ($viewDirs as $viewDir) {
            $paths = [
                $viewDir . '/' . $template,
                $viewDir . '/' . $template . '.php',
                $viewDir . '/' . $template . '.blu',
                $viewDir . '/' . $template . '.html',
                $viewDir . '/' . $template . '.twig',
            ];

            foreach ($paths as $path) {
                if (file_exists($path)) {
                    return $path;
                }
            }
        }

        return null;
    }

    /**
     * Render a bundle template.
     *
     * @param BundleInterface $bundle
     * @param string $template
     * @param array $data
     * @param mixed $templateService
     * @return string|null
     */
    public function render(BundleInterface $bundle, string $template, array $data = [], $templateService = null): ?string
    {
        $viewPath = $this->getViewPath($bundle, $template);
        if (!$viewPath) {
            return null;
        }

        // If template service is provided, use it
        if ($templateService && method_exists($templateService, 'render')) {
            return $templateService->render($viewPath, $data);
        }

        // Otherwise, use simple PHP rendering
        return $this->renderPhpTemplate($viewPath, $data);
    }

    /**
     * Render a PHP template.
     *
     * @param string $viewPath
     * @param array $data
     * @return string
     */
    private function renderPhpTemplate(string $viewPath, array $data): string
    {
        extract($data, EXTR_SKIP);

        ob_start();
        include $viewPath;
        return ob_get_clean();
    }

    /**
     * Copy bundle views to application views directory.
     *
     * @param BundleInterface $bundle
     * @param string $targetDir
     * @return array List of copied view files
     */
    public function publishViews(BundleInterface $bundle, string $targetDir = 'app/views/bundles'): array
    {
        $viewDirs = $this->getViewDirectories($bundle);
        $copied = [];

        foreach ($viewDirs as $viewDir) {
            $copied = array_merge($copied, $this->copyDirectory($viewDir, $targetDir . '/' . $bundle->getName()));
        }

        return $copied;
    }

    /**
     * Copy directory recursively.
     *
     * @param string $source
     * @param string $target
     * @return array
     */
    private function copyDirectory(string $source, string $target): array
    {
        if (!is_dir($target) && !mkdir($target, 0o755, true)) {
            return [];
        }

        $copied = [];
        $dir = opendir($source);

        if (!$dir) {
            return [];
        }

        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $sourcePath = $source . '/' . $file;
            $targetPath = $target . '/' . $file;

            if (is_dir($sourcePath)) {
                $copied = array_merge($copied, $this->copyDirectory($sourcePath, $targetPath));
            } else {
                if (copy($sourcePath, $targetPath)) {
                    $copied[] = $targetPath;
                }
            }
        }

        closedir($dir);
        return $copied;
    }
}
