<?php

declare(strict_types=1);

namespace Architect\Services\Mvc\Middleware;

use Architect\Services\Mvc\Middleware\Contracts\MiddlewareInterface;
use Architect\Services\Mvc\Middleware\Contracts\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Middleware Stack.
 * 
 * Manages a stack of middleware and processes requests through them.
 * 
 * @package Architect\Services\Mvc\Middleware
 */
class MiddlewareStack implements RequestHandlerInterface
{
    /** @var array<MiddlewareInterface> Middleware stack */
    private array $middleware = [];

    /** @var RequestHandlerInterface|null Fallback handler */
    private ?RequestHandlerInterface $fallbackHandler = null;

    /** @var int Current position in the stack */
    private int $position = 0;

    /**
     * Add middleware to the stack.
     * 
     * @param MiddlewareInterface $middleware Middleware instance
     * @return self
     */
    public function add(MiddlewareInterface $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    /**
     * Add multiple middleware to the stack.
     * 
     * @param array<MiddlewareInterface> $middleware Middleware instances
     * @return self
     */
    public function addMany(array $middleware): self
    {
        foreach ($middleware as $m) {
            $this->add($m);
        }
        return $this;
    }

    /**
     * Set fallback handler.
     * 
     * @param RequestHandlerInterface $handler Handler instance
     * @return self
     */
    public function setFallbackHandler(RequestHandlerInterface $handler): self
    {
        $this->fallbackHandler = $handler;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!isset($this->middleware[$this->position])) {
            if ($this->fallbackHandler !== null) {
                return $this->fallbackHandler->handle($request);
            }

            throw new \RuntimeException('No middleware available to handle request');
        }

        $middleware = $this->middleware[$this->position];
        $this->position++;

        return $middleware->process($request, $this);
    }

    /**
     * Process request through the stack.
     * 
     * @param ServerRequestInterface $request Request instance
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request): ResponseInterface
    {
        $this->position = 0;
        return $this->handle($request);
    }

    /**
     * Check if stack has middleware.
     * 
     * @return bool
     */
    public function hasMiddleware(): bool
    {
        return count($this->middleware) > 0;
    }

    /**
     * Get middleware count.
     * 
     * @return int
     */
    public function count(): int
    {
        return count($this->middleware);
    }

    /**
     * Reset the stack.
     * 
     * @return self
     */
    public function reset(): self
    {
        $this->middleware = [];
        $this->position = 0;
        $this->fallbackHandler = null;
        return $this;
    }

    /**
     * Create stack from array of middleware.
     * 
     * @param array<MiddlewareInterface> $middleware Middleware instances
     * @return self
     */
    public static function fromArray(array $middleware): self
    {
        $stack = new self();
        $stack->addMany($middleware);
        return $stack;
    }
}
