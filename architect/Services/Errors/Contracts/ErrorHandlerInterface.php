<?php

declare(strict_types=1);

namespace Architect\Services\Errors\Contracts;

/**
 * Interface for error handling (registering handlers).
 */
interface ErrorHandlerInterface
{
    /**
     * Initialize error handlers.
     */
    public function init(): void;

    /**
     * Handle PHP error.
     */
    public function handleError(int $errno, string $errstr, string $errfile, int $errline): bool;

    /**
     * Handle uncaught exception.
     */
    public function handleException(\Throwable $exception): void;

    /**
     * Handle shutdown (fatal errors).
     */
    public function handleShutdown(): void;
}
