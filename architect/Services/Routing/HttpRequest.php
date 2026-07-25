<?php

declare(strict_types=1);

namespace Architect\Services\Routing;

use Architect\Services\Routing\Contracts\RequestInterface;

/**
 * HTTP request implementation using PHP superglobals.
 */
final class HttpRequest implements RequestInterface
{
    private string $path;
    private array $segments;

    public function __construct()
    {
        $this->parseUrl();
    }

    /**
     * Parse URL from PHP superglobals.
     */
    private function parseUrl(): void
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $requestUri = strtok($requestUri, '?');

        $scriptName = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        if ($scriptName !== '/' && str_starts_with($requestUri, $scriptName)) {
            $requestUri = substr($requestUri, strlen($scriptName));
        }

        $this->path = $requestUri ?: '/';
        $this->segments = array_values(array_filter(explode('/', trim($this->path, '/'))));
    }

    /**
     * @inheritdoc
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @inheritdoc
     */
    public function getSegments(): array
    {
        return $this->segments;
    }

    /**
     * @inheritdoc
     */
    public function getParam(string $name, mixed $default = null): mixed
    {
        return $_REQUEST[$name] ?? $default;
    }

    /**
     * @inheritdoc
     */
    public function getParams(): array
    {
        return $_REQUEST;
    }
}
