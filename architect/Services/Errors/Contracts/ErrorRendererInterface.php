<?php

declare(strict_types=1);

namespace Architect\Services\Errors\Contracts;

/**
 * Interface for error rendering (displaying error pages).
 */
interface ErrorRendererInterface
{
    /**
     * Display 404 error page.
     */
    public function display404(string $message = 'Page not found'): void;

    /**
     * Display error page.
     */
    public function displayError(string $message, int $code = 500): void;

    /**
     * Display exception page.
     */
    public function displayException(\Throwable $exception): void;
}
