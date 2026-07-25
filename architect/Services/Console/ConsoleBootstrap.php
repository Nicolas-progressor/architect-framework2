<?php

declare(strict_types=1);

namespace Architect\Console;

/**
 * Console Bootstrap - loads commands from various sources
 */
class ConsoleBootstrap
{
    protected ConsoleKernel $kernel;
    protected array $paths;

    public function __construct(ConsoleKernel $kernel)
    {
        $this->kernel = $kernel;
        $this->paths = $this->getDefaultPaths();
    }

    /**
     * Get default paths for command discovery
     */
    protected function getDefaultPaths(): array
    {
        $root = defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__, 4);
        $appDir = defined('APP_DIR') ? APP_DIR : $root . '/app';

        return [
            'app' => "{$appDir}/Console/Commands",
            'architect' => dirname(__DIR__) . '/Console/Commands',
            'vendor' => $root . '/vendor',
        ];
    }

    /**
     * Bootstrap and register all commands
     */
    public function bootstrap(): self
    {
        // Register built-in commands (already done in arc entry point)

        // Discover commands from app/Console/Commands
        $this->discoverCommands($this->paths['app']);

        // Discover commands from Composer packages
        $this->discoverPackageCommands();

        return $this;
    }

    /**
     * Discover commands from a directory
     */
    protected function discoverCommands(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $this->registerCommandFile($file->getPathname());
        }
    }

    /**
     * Register a command from file
     */
    protected function registerCommandFile(string $path): void
    {
        // Extract namespace from file path
        $relativePath = str_replace($this->paths['app'] . '/', '', $path);
        $relativePath = str_replace('.php', '', $relativePath);

        $namespace = 'app\\Console\\Commands\\' . str_replace('/', '\\', $relativePath);

        // Check if class exists and is instantiable
        if (class_exists($namespace)) {
            $command = new $namespace();

            if ($command instanceof CommandInterface) {
                $this->kernel->registerCommand($command);
            }
        }
    }

    /**
     * Discover commands from Composer packages
     */
    protected function discoverPackageCommands(): void
    {
        // Look for commands in vendor packages
        // Packages can register commands via their own bootstrap

        // Example: architect/console-commands package
        $vendorCommandsPath = $this->paths['vendor'] . '/architect/console-commands/src/Commands';

        if (is_dir($vendorCommandsPath)) {
            $this->discoverCommands($vendorCommandsPath);
        }
    }

    /**
     * Add a custom command path
     */
    public function addCommandPath(string $path): self
    {
        $this->paths['custom'] = $path;
        $this->discoverCommands($path);

        return $this;
    }

    /**
     * Get the kernel
     */
    public function getKernel(): ConsoleKernel
    {
        return $this->kernel;
    }
}
