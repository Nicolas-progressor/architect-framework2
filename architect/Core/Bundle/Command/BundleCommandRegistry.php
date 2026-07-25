<?php

declare(strict_types=1);

namespace Architect\Core\Bundle\Command;

use Architect\Contracts\BundleInterface;
use Architect\Services\Console\CommandRegistry;

/**
 * Registers bundle commands in the console.
 */
class BundleCommandRegistry
{
    /**
     * Register commands from a bundle.
     *
     * @param BundleInterface $bundle
     * @param CommandRegistry $commandRegistry
     */
    public function register(BundleInterface $bundle, CommandRegistry $commandRegistry): void
    {
        $commands = $this->discoverCommands($bundle);

        foreach ($commands as $commandClass) {
            if (class_exists($commandClass)) {
                $commandRegistry->register($commandClass);
            }
        }
    }

    /**
     * Discover commands in a bundle.
     *
     * @param BundleInterface $bundle
     * @return string[]
     */
    private function discoverCommands(BundleInterface $bundle): array
    {
        $reflection = new \ReflectionClass($bundle);
        $bundleDir = dirname($reflection->getFileName());

        $commands = [];

        // Check for Commands directory
        $commandsDir = $bundleDir . '/Commands';
        if (is_dir($commandsDir)) {
            $commands = array_merge($commands, $this->scanDirectoryForCommands($commandsDir));
        }

        // Check for Console/Commands directory
        $consoleCommandsDir = $bundleDir . '/Console/Commands';
        if (is_dir($consoleCommandsDir)) {
            $commands = array_merge($commands, $this->scanDirectoryForCommands($consoleCommandsDir));
        }

        // Check for Command directory
        $commandDir = $bundleDir . '/Command';
        if (is_dir($commandDir)) {
            $commands = array_merge($commands, $this->scanDirectoryForCommands($commandDir));
        }

        return array_unique($commands);
    }

    /**
     * Scan directory for command classes.
     *
     * @param string $directory
     * @return string[]
     */
    private function scanDirectoryForCommands(string $directory): array
    {
        $commands = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $className = $this->getClassNameFromFile($file->getPathname());
                if ($className && $this->isCommandClass($className)) {
                    $commands[] = $className;
                }
            }
        }

        return $commands;
    }

    /**
     * Get class name from file.
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

        return null;
    }

    /**
     * Check if a class is a command class.
     *
     * @param string $className
     * @return bool
     */
    private function isCommandClass(string $className): bool
    {
        if (!class_exists($className)) {
            return false;
        }

        $reflection = new \ReflectionClass($className);

        // Check if class implements CommandInterface
        if ($reflection->implementsInterface('Architect\Services\Console\CommandInterface')) {
            return true;
        }

        // Check if class extends BaseCommand
        if ($reflection->isSubclassOf('Architect\Services\Console\BaseCommand')) {
            return true;
        }

        // Check if class name ends with "Command"
        if (str_ends_with($className, 'Command')) {
            return true;
        }

        return false;
    }

    /**
     * Register commands from all bundles.
     *
     * @param array $bundles
     * @param CommandRegistry $commandRegistry
     */
    public function registerAll(array $bundles, CommandRegistry $commandRegistry): void
    {
        foreach ($bundles as $bundle) {
            $this->register($bundle, $commandRegistry);
        }
    }
}
