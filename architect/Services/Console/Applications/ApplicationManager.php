<?php

declare(strict_types=1);

namespace Architect\Console\Applications;

use InvalidArgumentException;

/**
 * Manages console applications
 */
class ApplicationManager
{
    /** @var array<string, ConsoleApplication> */
    protected array $applications = [];

    /**
     * Register a console application
     *
     * @throws InvalidArgumentException
     */
    public function register(ConsoleApplication $application): self
    {
        $name = $application->getName();

        if (empty($name)) {
            throw new InvalidArgumentException('Application name cannot be empty');
        }

        if (isset($this->applications[$name])) {
            throw new InvalidArgumentException("Application '{$name}' is already registered");
        }

        $this->applications[$name] = $application;

        return $this;
    }

    /**
     * Remove an application
     */
    public function unregister(string $name): self
    {
        unset($this->applications[$name]);

        return $this;
    }

    /**
     * Get an application
     */
    public function get(string $name): ?ConsoleApplication
    {
        return $this->applications[$name] ?? null;
    }

    /**
     * Check if application exists
     */
    public function has(string $name): bool
    {
        return isset($this->applications[$name]);
    }

    /**
     * Get all applications
     *
     * @return array<string, ConsoleApplication>
     */
    public function all(): array
    {
        return $this->applications;
    }

    /**
     * Bootstrap an application
     */
    public function bootstrap(string $name): void
    {
        $application = $this->get($name);

        if ($application) {
            $application->bootstrap();
        }
    }

    /**
     * Get all commands from all applications
     *
     * @return array<int, ConsoleApplication>
     */
    public function getAllCommands(): array
    {
        $commands = [];

        foreach ($this->applications as $application) {
            $commands = array_merge($commands, $application->getCommands());
        }

        return $commands;
    }

    /**
     * Get application count
     */
    public function count(): int
    {
        return count($this->applications);
    }
}
