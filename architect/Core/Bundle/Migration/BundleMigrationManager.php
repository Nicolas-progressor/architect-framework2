<?php

declare(strict_types=1);

namespace Architect\Core\Bundle\Migration;

use Architect\Contracts\BundleInterface;
use RuntimeException;

/**
 * Manages bundle migrations.
 */
class BundleMigrationManager
{
    /**
     * Get migration directory for a bundle.
     *
     * @param BundleInterface $bundle
     * @return string|null
     */
    public function getMigrationDirectory(BundleInterface $bundle): ?string
    {
        $reflection = new \ReflectionClass($bundle);
        $bundleDir = dirname($reflection->getFileName());

        // Try Migrations directory
        $migrationsDir = $bundleDir . '/Migrations';
        if (is_dir($migrationsDir)) {
            return $migrationsDir;
        }

        // Try migrations directory
        $migrationsDir = $bundleDir . '/migrations';
        if (is_dir($migrationsDir)) {
            return $migrationsDir;
        }

        // Try Resources/migrations directory
        $migrationsDir = $bundleDir . '/Resources/migrations';
        if (is_dir($migrationsDir)) {
            return $migrationsDir;
        }

        return null;
    }

    /**
     * Get all migration files for a bundle.
     *
     * @param BundleInterface $bundle
     * @return string[]
     */
    public function getMigrationFiles(BundleInterface $bundle): array
    {
        $migrationDir = $this->getMigrationDirectory($bundle);
        if (!$migrationDir) {
            return [];
        }

        $files = [];
        $iterator = new \DirectoryIterator($migrationDir);

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        // Sort files by name (which should include timestamp)
        usort($files, function ($a, $b) {
            return basename($a) <=> basename($b);
        });

        return $files;
    }

    /**
     * Copy bundle migrations to application migrations directory.
     *
     * @param BundleInterface $bundle
     * @param string $targetDir
     * @return array List of copied migration files
     */
    public function publishMigrations(BundleInterface $bundle, string $targetDir = 'migrations'): array
    {
        $migrationFiles = $this->getMigrationFiles($bundle);
        $copied = [];

        foreach ($migrationFiles as $sourceFile) {
            $filename = basename($sourceFile);
            $targetFile = $targetDir . '/' . $filename;

            // Check if migration already exists
            if (file_exists($targetFile)) {
                continue;
            }

            if (copy($sourceFile, $targetFile)) {
                $copied[] = $targetFile;
            }
        }

        return $copied;
    }

    /**
     * Publish migrations for all bundles.
     *
     * @param array $bundles
     * @param string $targetDir
     * @return array
     */
    public function publishAllMigrations(array $bundles, string $targetDir = 'migrations'): array
    {
        $allCopied = [];

        foreach ($bundles as $bundle) {
            $copied = $this->publishMigrations($bundle, $targetDir);
            $allCopied[$bundle->getName()] = $copied;
        }

        return $allCopied;
    }

    /**
     * Get migration namespace for a bundle.
     *
     * @param BundleInterface $bundle
     * @return string
     */
    public function getMigrationNamespace(BundleInterface $bundle): string
    {
        $reflection = new \ReflectionClass($bundle);
        $bundleNamespace = $reflection->getNamespaceName();

        return $bundleNamespace . '\\Migrations';
    }

    /**
     * Load migration classes from a bundle.
     *
     * @param BundleInterface $bundle
     * @return string[]
     */
    public function loadMigrationClasses(BundleInterface $bundle): array
    {
        $migrationFiles = $this->getMigrationFiles($bundle);
        $classes = [];

        foreach ($migrationFiles as $file) {
            $className = $this->getClassNameFromFile($file);
            if ($className) {
                $classes[] = $className;
            }
        }

        return $classes;
    }

    /**
     * Get class name from migration file.
     *
     * @param string $filePath
     * @return string|null
     */
    private function getClassNameFromFile(string $filePath): ?string
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return null;
        }

        $tokens = token_get_all($content);
        $namespace = '';
        $className = '';

        for ($i = 0; isset($tokens[$i]); $i++) {
            if ($tokens[$i][0] === T_NAMESPACE) {
                for ($j = $i + 1; isset($tokens[$j]); $j++) {
                    if ($tokens[$j][0] === T_STRING) {
                        $namespace .= '\\' . $tokens[$j][1];
                    } elseif ($tokens[$j] === '{' || $tokens[$j] === ';') {
                        break;
                    }
                }
            }

            if ($tokens[$i][0] === T_CLASS) {
                for ($j = $i + 1; isset($tokens[$j]); $j++) {
                    if ($tokens[$j] === '{') {
                        $className = ltrim($namespace . '\\' . $className, '\\');
                        return $className;
                    }
                    if ($tokens[$j][0] === T_STRING) {
                        $className = $tokens[$j][1];
                    }
                }
            }
        }

        // If no namespace found, try to extract from file path
        $filename = basename($filePath, '.php');
        return $filename;
    }

    /**
     * Create migration directory for a bundle if it doesn't exist.
     *
     * @param BundleInterface $bundle
     * @return string
     */
    public function ensureMigrationDirectory(BundleInterface $bundle): string
    {
        $reflection = new \ReflectionClass($bundle);
        $bundleDir = dirname($reflection->getFileName());

        $migrationDir = $bundleDir . '/Migrations';
        if (!is_dir($migrationDir)) {
            if (!mkdir($migrationDir, 0o755, true)) {
                throw new RuntimeException("Unable to create migration directory: {$migrationDir}");
            }
        }

        return $migrationDir;
    }
}
