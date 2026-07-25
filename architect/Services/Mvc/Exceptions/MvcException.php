<?php

declare(strict_types=1);

namespace Architect\Services\Mvc\Exceptions;

use RuntimeException;

/**
 * Base MVC exception.
 * 
 * All MVC-specific exceptions extend this class.
 * Provides context information for debugging.
 * 
 * @package Architect\Services\Mvc\Exceptions
 */
class MvcException extends RuntimeException
{
    /** @var string Additional context information */
    protected string $context = '';

    /**
     * Create exception with context.
     * 
     * @param string $message Error message
     * @param string $context Additional context
     * @return self
     */
    public static function withContext(string $message, string $context = ''): self
    {
        $exception = new self($message);
        $exception->context = $context;
        return $exception;
    }

    /**
     * Get exception context.
     * 
     * @return string
     */
    public function getContext(): string
    {
        return $this->context;
    }
}
