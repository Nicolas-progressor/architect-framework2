<?php

declare(strict_types=1);

namespace Blueprint\Engine\Template;

/**
 * Debug Integration
 * 
 * Handles integration with Debug service for logging
 * template compilation, errors, and cache information.
 * 
 * @package Blueprint\Engine\Template
 */
class DebugIntegration
{
    protected ?object $container = null;
    protected ?object $debug = null;

    public function __construct(?object $container = null)
    {
        $this->container = $container;
        $this->initDebug();
    }

    /**
     * Initialize debug service reference
     */
    protected function initDebug(): void
    {
        if ($this->container === null) {
            return;
        }
        
        try {
            $this->debug = $this->container->get('debug');
        } catch (\Exception $e) {
            $this->debug = null;
        }
    }

    /**
     * Check if debug is enabled
     */
    public function isEnabled(): bool
    {
        return $this->debug !== null && $this->debug->isEnabled();
    }

    /**
     * Log template compilation
     */
    public function logCompile(string $template, string $compiledPath, bool $fromCache): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        try {
            $this->debug->blueprintCompile($template, $compiledPath, $fromCache);
        } catch (\Exception $e) {
            // Ignore debug errors
        }
    }

    /**
     * Log Blueprint error
     */
    public function logError(string $template, string $message, ?string $compiledCode = null): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        try {
            $this->debug->blueprintError($template, $message, $compiledCode);
        } catch (\Exception $e) {
            // Ignore debug errors
        }
    }

    /**
     * Initialize Blueprint data in Debug panel
     */
    public function initData(array $loaderPaths, bool $cacheEnabled, string $cachePath): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        try {
            $this->debug->setBlueprintData([
                'enabled' => true,
                'loader_paths' => $loaderPaths,
                'cache_enabled' => $cacheEnabled,
                'cache_path' => $cachePath,
            ]);
            
            // Add cache information
            $cacheFiles = [];
            
            if (is_dir($cachePath)) {
                $files = glob($cachePath . '/*.php');
                foreach ($files as $file) {
                    $cacheFiles[] = [
                        'name' => basename($file),
                        'path' => $file,
                        'size' => filesize($file),
                        'modified' => filemtime($file),
                    ];
                }
            }
            
            $this->debug->setBlueprintData([
                'cache' => [
                    'enabled' => $cacheEnabled,
                    'path' => $cachePath,
                    'files_count' => count($cacheFiles),
                    'files' => $cacheFiles,
                ]
            ]);
        } catch (\Exception $e) {
            // Ignore debug errors
        }
    }

    /**
     * Add custom debug data
     */
    public function addData(string $key, array $data): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        try {
            $bpData = $this->debug->getBlueprintData();
            if (!isset($bpData['debug_info'])) {
                $bpData['debug_info'] = [];
            }
            $bpData['debug_info'][$key] = $data;
            $this->debug->setBlueprintData($bpData);
        } catch (\Exception $e) {
            // Ignore debug errors
        }
    }

    /**
     * Set container
     */
    public function setContainer(?object $container): self
    {
        $this->container = $container;
        $this->initDebug();
        return $this;
    }
}
