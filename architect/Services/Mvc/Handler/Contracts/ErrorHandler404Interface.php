<?php

declare(strict_types=1);

namespace Architect\Services\Mvc\Handler\Contracts;

/**
 * Interface for 404 error handling.
 * 
 * Defines the contract for handling 404 errors in MVC context.
 * 
 * @package Architect\Services\Mvc\Handler\Contracts
 */
interface ErrorHandler404Interface
{
    /**
     * Handle 404 error with view rendering.
     * 
     * Renders 404 view and sets content in template.
     * 
     * @param string $message Error message
     */
    public function handle(string $message = 'Page not found'): void;

    /**
     * Handle fatal 404 error.
     * 
     * Displays error page and exits execution.
     * 
     * @param string $message Error message
     */
    public function handleFatal(string $message = 'Page not found'): void;

    /**
     * Check if app-level 404 controller exists.
     * 
     * @return bool
     */
    public function hasApp404(): bool;

    /**
     * Check if global 404 controller exists.
     * 
     * @return bool
     */
    public function hasGlobal404(): bool;
}
