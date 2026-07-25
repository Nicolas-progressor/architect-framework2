<?php

declare(strict_types=1);

namespace Architect\Core\Bundle;

use Composer\InstalledVersions;
use RuntimeException;

/**
 * Discovers bundles from installed Composer packages.
 */
class BundleDiscovery
{
    /**
     * Cache file path relative to project root.
     */
    public const CACHE_FILE = 'bootstrap/cache/bundles.php';

    /**
     * Discover all bundles from installed packages.
     *
     * @return string[] Array of fully qualified bundle class names
     */
    public static function discover(): array
    {
        $bundles = [];

        // Get root package extra config
        $rootPackage = self::getComposerRootPackage();
        if (isset($rootPackage['extra']['architect']['bundles'])) {
            $bundles = array_merge($bundles, $rootPackage['extra']['architect']['bundles']);
        }

        // Get installed packages
        $packages = self::getInstalledPackages();
        foreach ($packages as $package) {
            if (isset($package['extra']['architect']['bundles'])) {
                $bundles = array_merge($bundles, $package['extra']['architect']['bundles']);
            }
        }

        // Remove duplicates and ensure strings
        $bundles = array_unique(array_filter($bundles, 'is_string'));

        // Sort for deterministic output
        sort($bundles);

        return $bundles;
    }

    /**
     * Generate cache file with discovered bundles.
     *
     * @throws RuntimeException If cache directory is not writable
     */
    public static function cache(): void
    {
        $bundles = self::discover();
        $cachePath = ROOT_DIR . self::CACHE_FILE;

        // Ensure cache directory exists
        $cacheDir = dirname($cachePath);
        if (!is_dir($cacheDir) && !mkdir($cacheDir, 0755, true)) {
            throw new RuntimeException("Unable to create cache directory: {$cacheDir}");
        }

        $content = "<?php\n\nreturn " . var_export($bundles, true) . ";\n";
        if (file_put_contents($cachePath, $content) === false) {
            throw new RuntimeException("Unable to write cache file: {$cachePath}");
        }
    }

    /**
     * Load bundles from cache file.
     *
     * @return string[] Array of bundle class names
     */
    public static function loadFromCache(): array
    {
        $cachePath = ROOT_DIR . self::CACHE_FILE;
        if (!file_exists($cachePath)) {
            return [];
        }

        $bundles = require $cachePath;
        if (!is_array($bundles)) {
            return [];
        }

        return $bundles;
    }

    /**
     * Get Composer root package extra data.
     *
     * @return array
     */
    private static function getComposerRootPackage(): array
    {
        static $rootPackage = null;
        if ($rootPackage === null) {
            $composerFile = ROOT_DIR . 'composer.json';
            if (!file_exists($composerFile)) {
                $rootPackage = [];
                return $rootPackage;
            }
            $content = file_get_contents($composerFile);
            $data = json_decode($content, true);
            $rootPackage = is_array($data) ? $data : [];
        }
        return $rootPackage;
    }

    /**
     * Get installed packages data.
     *
     * @return array[]
     */
    private static function getInstalledPackages(): array
    {
        // Use Composer's InstalledVersions if available (Composer 2)
        if (class_exists('Composer\InstalledVersions')) {
            $packages = [];
            $rootPackageName = InstalledVersions::getRootPackage()['name'] ?? '';
            foreach (InstalledVersions::getAllRawData() as $vendor) {
                foreach ($vendor['versions'] as $packageName => $packageData) {
                    // Skip root package
                    if ($packageName === $rootPackageName) {
                        continue;
                    }
                    if (isset($packageData['install_path'])) {
                        $composerFile = $packageData['install_path'] . '/composer.json';
                        if (file_exists($composerFile)) {
                            $content = file_get_contents($composerFile);
                            $data = json_decode($content, true);
                            if (is_array($data)) {
                                $packages[] = $data;
                            }
                        }
                    }
                }
            }
            return $packages;
        }

        // Fallback: scan vendor/composer/installed.json
        $installedFile = ROOT_DIR . 'vendor/composer/installed.json';
        if (file_exists($installedFile)) {
            $content = file_get_contents($installedFile);
            $data = json_decode($content, true);
            if (isset($data['packages'])) {
                return $data['packages'];
            }
            if (isset($data['dev-packages'])) {
                return array_merge($data['packages'] ?? [], $data['dev-packages']);
            }
        }

        return [];
    }
}