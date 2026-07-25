<?php

declare(strict_types=1);

namespace Architect\Console;

/**
 * Console Factory - creates and configures ConsoleKernel instances
 *
 * Usage:
 *   $console = (new ConsoleFactory())->create();
 *   $exitCode = $console->run();
 *
 * Or programmatically run a command:
 *   $consoleFactory = new ConsoleFactory();
 *   $exitCode = $consoleFactory->runCommand('make:controller', ['UserController']);
 */
class ConsoleFactory
{
    protected ?CommandRegistry $registry = null;
    protected array $commandPaths = [];
    protected bool $autoDiscoverCommands = true;

    /**
     * Create a new factory instance
     */
    public function __construct(?CommandRegistry $registry = null)
    {
        $this->registry = $registry;
        $this->addCommandPath($this->getDefaultCommandPath());
    }

    /**
     * Get default command path
     */
    protected function getDefaultCommandPath(): string
    {
        return dirname(__DIR__) . '/Console/Commands';
    }

    /**
     * Add a custom command path for discovery
     */
    public function addCommandPath(string $path): self
    {
        if (is_dir($path)) {
            $this->commandPaths[] = $path;
        }

        return $this;
    }

    /**
     * Enable/disable auto-discovery of built-in commands
     */
    public function setAutoDiscoverCommands(bool $enabled): self
    {
        $this->autoDiscoverCommands = $enabled;

        return $this;
    }

    /**
     * Register a command manually
     */
    public function registerCommand(CommandInterface $command): self
    {
        $this->ensureRegistry();
        $this->registry->register($command);

        return $this;
    }

    /**
     * Register multiple commands
     *
     * @param array<int, CommandInterface> $commands
     */
    public function registerCommands(array $commands): self
    {
        $this->ensureRegistry();

        foreach ($commands as $command) {
            $this->registry->register($command);
        }

        return $this;
    }

    /**
     * Ensure registry is initialized
     */
    protected function ensureRegistry(): void
    {
        if ($this->registry === null) {
            $this->registry = new CommandRegistry();
        }
    }

    /**
     * Create and configure the console kernel
     */
    public function create(): ConsoleKernel
    {
        $this->ensureRegistry();

        $kernel = new ConsoleKernel($this->registry);

        // Discover commands from paths
        if ($this->autoDiscoverCommands) {
            $this->discoverCommands($kernel);
        }

        return $kernel;
    }

    /**
     * Discover and register commands from configured paths
     */
    protected function discoverCommands(ConsoleKernel $kernel): void
    {
        foreach ($this->commandPaths as $path) {
            $this->discoverCommandsInPath($path, $kernel);
        }
    }

    /**
     * Discover commands in a specific directory
     */
    protected function discoverCommandsInPath(string $path, ConsoleKernel $kernel): void
    {
        if (!is_dir($path)) {
            return;
        }

        $files = glob("{$path}/*Command.php");

        if (!$files) {
            return;
        }

        foreach ($files as $file) {
            $className = basename($file, '.php');

            // Determine namespace based on path
            $relativePath = str_replace($path . '/', '', $file);
            $relativePath = str_replace('.php', '', $relativePath);
            $namespace = $this->determineNamespace($path, $relativePath);

            $fullClassName = $namespace . '\\' . $className;

            if (!class_exists($fullClassName)) {
                continue;
            }

            $command = new $fullClassName();

            if ($command instanceof CommandInterface) {
                $kernel->registerCommand($command);
            }
        }
    }

    /**
     * Determine namespace from path
     */
    protected function determineNamespace(string $basePath, string $relativePath): string
    {
        // Architect built-in commands
        if (str_contains($basePath, 'Architect/Console/Commands')) {
            return 'Architect\\Console\\Commands';
        }

        // App commands
        if (defined('APP_DIR') && str_contains($basePath, APP_DIR)) {
            return 'app\\Console\\Commands';
        }

        // Default
        return 'Architect\\Console\\Commands';
    }

    /**
     * Run a specific command programmatically
     */
    public function runCommand(string $commandName, array $arguments = [], array $options = []): int
    {
        $kernel = $this->create();

        // Build argv
        $argv = array_merge(['', $commandName], $arguments);

        foreach ($options as $key => $value) {
            if (is_bool($value) && $value) {
                $argv[] = "--{$key}";
            } elseif (!is_bool($value)) {
                $argv[] = "--{$key}={$value}";
            }
        }

        $input = new Input($argv);

        return $kernel->run($input);
    }

    /**
     * Get the command registry
     */
    public function getRegistry(): CommandRegistry
    {
        $this->ensureRegistry();

        return $this->registry;
    }
}
