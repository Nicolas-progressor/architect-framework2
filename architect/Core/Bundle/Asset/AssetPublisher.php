<?php

declare(strict_types=1);

namespace Architect\Core\Bundle\Asset;

use Architect\Contracts\BundleInterface;
use RuntimeException;

/**
 * Publishes bundle assets to public directory.
 */
class AssetPublisher
{
    /**
     * Publish bundle assets.
     *
     * @param BundleInterface $bundle
     * @param string $targetDir
     * @return array List of published files
     */
    public function publish(BundleInterface $bundle, string $targetDir = 'htdocs/assets/bundles'): array
    {
        $bundleName = $bundle->getName();
        $sourceDir = $this->getBundleAssetsPath($bundle);

        if (!$sourceDir || !is_dir($sourceDir)) {
            return [];
        }

        $targetBundleDir = $targetDir . '/' . strtolower($bundleName);

        // Create target directory if it doesn't exist
        if (!is_dir($targetBundleDir) && !mkdir($targetBundleDir, 0o755, true)) {
            throw new RuntimeException("Unable to create directory: {$targetBundleDir}");
        }

        $published = [];
        $this->copyDirectory($sourceDir, $targetBundleDir, $published);

        return $published;
    }

    /**
     * Get the path to bundle's assets directory.
     *
     * @param BundleInterface $bundle
     * @return string|null
     */
    private function getBundleAssetsPath(BundleInterface $bundle): ?string
    {
        $reflection = new \ReflectionClass($bundle);
        $bundleDir = dirname($reflection->getFileName());

        // Try Resources/public
        $assetsPath = $bundleDir . '/Resources/public';
        if (is_dir($assetsPath)) {
            return $assetsPath;
        }

        // Try public
        $assetsPath = $bundleDir . '/public';
        if (is_dir($assetsPath)) {
            return $assetsPath;
        }

        // Try assets
        $assetsPath = $bundleDir . '/assets';
        if (is_dir($assetsPath)) {
            return $assetsPath;
        }

        return null;
    }

    /**
     * Copy directory recursively.
     *
     * @param string $source
     * @param string $target
     * @param array $published
     */
    private function copyDirectory(string $source, string $target, array &$published): void
    {
        if (!is_dir($target) && !mkdir($target, 0o755, true)) {
            throw new RuntimeException("Unable to create directory: {$target}");
        }

        $dir = opendir($source);
        if (!$dir) {
            return;
        }

        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $sourcePath = $source . '/' . $file;
            $targetPath = $target . '/' . $file;

            if (is_dir($sourcePath)) {
                $this->copyDirectory($sourcePath, $targetPath, $published);
            } else {
                if (copy($sourcePath, $targetPath)) {
                    $published[] = $targetPath;
                }
            }
        }

        closedir($dir);
    }

    /**
     * Publish assets for all bundles.
     *
     * @param array $bundles
     * @param string $targetDir
     * @return array
     */
    public function publishAll(array $bundles, string $targetDir = 'htdocs/assets/bundles'): array
    {
        $allPublished = [];

        foreach ($bundles as $bundle) {
            $published = $this->publish($bundle, $targetDir);
            $allPublished[$bundle->getName()] = $published;
        }

        return $allPublished;
    }

    /**
     * Clear published assets for a bundle.
     *
     * @param BundleInterface $bundle
     * @param string $targetDir
     * @return bool
     */
    public function clear(BundleInterface $bundle, string $targetDir = 'htdocs/assets/bundles'): bool
    {
        $bundleName = $bundle->getName();
        $targetBundleDir = $targetDir . '/' . strtolower($bundleName);

        if (!is_dir($targetBundleDir)) {
            return true;
        }

        return $this->removeDirectory($targetBundleDir);
    }

    /**
     * Remove directory recursively.
     *
     * @param string $dir
     * @return bool
     */
    private function removeDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return true;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        return rmdir($dir);
    }
}
