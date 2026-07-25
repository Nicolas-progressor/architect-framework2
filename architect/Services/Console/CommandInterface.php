<?php

declare(strict_types=1);

namespace Architect\Console;

/**
 * Interface for console commands
 */
interface CommandInterface
{
    /**
     * Get command name
     */
    public function getName(): string;

    /**
     * Get command description
     */
    public function getDescription(): string;

    /**
     * Execute the command
     *
     * @param array<string, mixed> $arguments Positional arguments
     * @param array<string, mixed> $options Optional arguments (flags)
     * @return int Exit code (0 for success, non-zero for error)
     */
    public function execute(array $arguments, array $options): int;

    /**
     * Get command arguments definition
     *
     * @return array<int, array{0: string, 1: string}>
     */
    public function getArguments(): array;

    /**
     * Get command options definition
     *
     * @return array<int, array{0: string, 1: string}>
     */
    public function getOptions(): array;

    /**
     * Check if command requires authentication
     */
    public function requiresAuth(): bool;

    /**
     * Get command aliases
     *
     * @return array<int, string>
     */
    public function getAliases(): array;
}
