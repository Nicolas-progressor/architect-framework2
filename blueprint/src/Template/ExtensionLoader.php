<?php

declare(strict_types=1);

namespace Blueprint\Engine\Template;

use Blueprint\Engine\BlueprintExtension;

/**
 * Extension Loader
 * 
 * Loads and registers Blueprint extensions from Composer packages.
 * Extensions are discovered via package's extra.blueprint.extension config.
 * 
 * @package Blueprint\Engine\Template
 */
class ExtensionLoader
{
    /**
     * Load extensions from installed Composer packages
     */
    public function loadExtensions(object $blueprint): void
    {
        if (!class_exists(\Composer\InstalledVersions::class)) {
            return;
        }

        try {
            $installedPackages = \Composer\InstalledVersions::getInstalledPackages();
        } catch (\Exception $e) {
            return;
        }

        foreach ($installedPackages as $packageName) {
            if ($packageName === 'architect/blueprint') {
                continue;
            }

            $extensionClass = $this->findExtensionClass($packageName);
            
            if ($extensionClass === null) {
                continue;
            }

            $this->registerExtension($extensionClass, $blueprint);
        }
    }

    /**
     * Find extension class for package
     */
    protected function findExtensionClass(string $packageName): ?string
    {
        try {
            $installPath = \Composer\InstalledVersions::getInstallPath($packageName);
            
            if ($installPath === null || !is_dir($installPath)) {
                return null;
            }

            $composerFile = $installPath . '/composer.json';
            if (!file_exists($composerFile)) {
                return null;
            }

            $composerData = json_decode(file_get_contents($composerFile), true);
            $extra = $composerData['extra'] ?? [];
            
            if (!isset($extra['blueprint']['extension'])) {
                return null;
            }

            return $extra['blueprint']['extension'];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Register extension with Blueprint
     */
    protected function registerExtension(string $extensionClass, object $blueprint): void
    {
        if (!class_exists($extensionClass)) {
            return;
        }

        $interfaces = class_implements($extensionClass);
        if (!isset($interfaces[BlueprintExtension::class])) {
            return;
        }

        try {
            $extension = new $extensionClass();
            $extension->register($blueprint);
        } catch (\Exception $e) {
            // Skip failed extensions
        }
    }
}
