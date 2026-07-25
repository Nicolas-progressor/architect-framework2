<?php

declare(strict_types=1);

namespace Architect\Console\Applications;

use Architect\Console\CommandInterface;

/**
 * Base class for console applications
 */
abstract class ConsoleApplication
{
    protected string $name;
    protected string $description;
    protected string $version = '1.0.0';

    /** @var array<int, CommandInterface> */
    protected array $commands = [];

    /**
     * Register application commands
     */
    abstract public function register(): void;

    /**
     * Bootstrap the application
     */
    public function bootstrap(): void
    {
        // Override in subclass if needed
    }

    /**
     * Get application name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get application description
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Get application version
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Register a command
     */
    protected function registerCommand(CommandInterface $command): void
    {
        $this->commands[] = $command;
    }

    /**
     * Register multiple commands
     *
     * @param array<int, CommandInterface> $commands
     */
    protected function registerCommands(array $commands): void
    {
        foreach ($commands as $command) {
            $this->registerCommand($command);
        }
    }

    /**
     * Get all registered commands
     *
     * @return array<int, CommandInterface>
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * Get command by name
     */
    public function getCommand(string $name): ?CommandInterface
    {
        foreach ($this->commands as $command) {
            if ($command->getName() === $name) {
                return $command;
            }

            if (in_array($name, $command->getAliases(), true)) {
                return $command;
            }
        }

        return null;
    }
}
