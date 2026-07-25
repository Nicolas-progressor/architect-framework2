<?php

declare(strict_types=1);

namespace Architect\Services\Routing\Contracts;

/**
 * Interface for HTTP request abstraction.
 */
interface RequestInterface
{
    /**
     * Get current request path.
     */
    public function getPath(): string;

    /**
     * Get URL segments.
     * @return string[]
     */
    public function getSegments(): array;

    /**
     * Get request parameter by name.
     */
    public function getParam(string $name, mixed $default = null): mixed;

    /**
     * Get all request parameters.
     */
    public function getParams(): array;
}
