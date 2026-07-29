<?php

declare(strict_types=1);

namespace Architect\Core\Bundle\Config;

use Architect\Contracts\BundleInterface;
use Architect\Contracts\Core\ContainerInterface;

/**
 * Loads configuration for bundles.
 */
class BundleConfigLoader
{
    /**
     * Load configuration for a bundle.
     *
     * @param BundleInterface $bundle
     * @param ContainerInterface $container
     * @return array
     */
    public function load(BundleInterface $bundle, ContainerInterface $container): array
    {
        $bundleName = $bundle->getName();
        $config = [];

        // Try to load from bundle's Resources/config directory
        $configPath = $this->getBundleConfigPath($bundle);
        if ($configPath && file_exists($configPath)) {
            $content = file_get_contents($configPath);
            if ($content !== false) {
                $data = json_decode($content, true);
                if (is_array($data)) {
                    $config = array_merge($config, $data);
                }
            }
        }

        // Try to load from app/config/bundles/{bundleName}.json
        $appConfigPath = ROOT_DIR . "app/config/bundles/{$bundleName}.json";
        if (file_exists($appConfigPath)) {
            $content = file_get_contents($appConfigPath);
            if ($content !== false) {
                $data = json_decode($content, true);
                if (is_array($data)) {
                    $config = array_merge($config, $data);
                }
            }
        }

        // Try to load from environment-specific config
        $env = $container->has('environment') ? $container->get('environment')->getEnvironment() : 'development';
        $envConfigPath = ROOT_DIR . "app/config/bundles/{$bundleName}.{$env}.json";
        if (file_exists($envConfigPath)) {
            $content = file_get_contents($envConfigPath);
            if ($content !== false) {
                $data = json_decode($content, true);
                if (is_array($data)) {
                    $config = array_merge($config, $data);
                }
            }
        }

        return $config;
    }

    /**
     * Get the path to bundle's configuration file.
     *
     * @param BundleInterface $bundle
     * @return string|null
     */
    private function getBundleConfigPath(BundleInterface $bundle): ?string
    {
        $reflection = new \ReflectionClass($bundle);
        $bundleDir = dirname($reflection->getFileName());

        // Try Resources/config/config.json
        $configPath = $bundleDir . '/Resources/config/config.json';
        if (file_exists($configPath)) {
            return $configPath;
        }

        // Try config/config.json
        $configPath = $bundleDir . '/config/config.json';
        if (file_exists($configPath)) {
            return $configPath;
        }

        return null;
    }

    /**
     * Load all bundles configuration.
     *
     * @param array $bundles
     * @param ContainerInterface $container
     * @return array
     */
    public function loadAll(array $bundles, ContainerInterface $container): array
    {
        $config = [];

        foreach ($bundles as $bundle) {
            $bundleName = $bundle->getName();
            $config[$bundleName] = $this->load($bundle, $container);
        }

        return $config;
    }
}
