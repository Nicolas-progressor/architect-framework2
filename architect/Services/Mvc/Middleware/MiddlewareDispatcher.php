<?php

declare(strict_types=1);

namespace Architect\Services\Mvc\Middleware;

use Architect\Services\Mvc\Middleware\Contracts\MiddlewareInterface;
use Architect\Services\Mvc\Middleware\Contracts\RequestHandlerInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Middleware Dispatcher.
 *
 * Dispatches middleware for controller actions.
 *
 * @package Architect\Services\Mvc\Middleware
 */
class MiddlewareDispatcher
{
    /** @var ContainerInterface Container instance */
    private ContainerInterface $container;

    /** @var MiddlewareResolver Resolver instance */
    private MiddlewareResolver $resolver;

    /** @var ResponseFactoryInterface Response factory */
    private ResponseFactoryInterface $responseFactory;

    /** @var array<string, array> Controller middleware configuration */
    private array $controllerMiddleware = [];

    /**
     * Create dispatcher instance.
     *
     * @param ContainerInterface $container Container instance
     * @param ResponseFactoryInterface|null $responseFactory Response factory
     */
    public function __construct(
        ContainerInterface $container,
        ?ResponseFactoryInterface $responseFactory = null
    ) {
        $this->container = $container;
        $this->resolver = new MiddlewareResolver($container);
        $this->responseFactory = $responseFactory ?? $container->get('http.response_factory');
    }

    /**
     * Register controller middleware.
     *
     * @param string $controllerClass Controller class name
     * @param array $middleware Middleware configuration
     * @return self
     */
    public function register(string $controllerClass, array $middleware): self
    {
        $this->controllerMiddleware[$controllerClass] = $middleware;
        return $this;
    }

    /**
     * Dispatch middleware for action.
     *
     * @param string $controllerClass Controller class name
     * @param string $action Action name
     * @param ServerRequestInterface $request Request instance
     * @param callable $next Next handler (controller action)
     * @return ResponseInterface
     */
    public function dispatch(
        string $controllerClass,
        string $action,
        ServerRequestInterface $request,
        callable $next
    ): ResponseInterface {
        $middleware = $this->getMiddlewareForAction($controllerClass, $action);

        if (empty($middleware)) {
            return $next($request);
        }

        $stack = $this->createStack($middleware, $next);
        return $stack->process($request);
    }

    /**
     * Get middleware for action.
     *
     * @param string $controllerClass Controller class name
     * @param string $action Action name
     * @return array<MiddlewareInterface>
     */
    protected function getMiddlewareForAction(string $controllerClass, string $action): array
    {
        $config = $this->controllerMiddleware[$controllerClass] ?? [];
        $result = [];

        foreach ($config as $item) {
            $middleware = $this->resolveMiddlewareItem($item, $action);

            if ($middleware !== null) {
                $result[] = $middleware;
            }
        }

        return $result;
    }

    /**
     * Resolve middleware item.
     *
     * @param mixed $item Middleware item
     * @param string $action Action name
     * @return MiddlewareInterface|null
     */
    protected function resolveMiddlewareItem(mixed $item, string $action): ?MiddlewareInterface
    {
        // Simple class name or alias
        if (is_string($item)) {
            return $this->resolver->resolve($item);
        }

        // Array with options: ['class' => 'Auth', 'only' => ['edit', 'delete']]
        if (is_array($item) && isset($item[0])) {
            $middleware = $this->resolver->resolve($item[0]);

            // Check "only" constraint
            if (isset($item['only']) && !in_array($action, (array) $item['only'], true)) {
                return null;
            }

            // Check "except" constraint
            if (isset($item['except']) && in_array($action, (array) $item['except'], true)) {
                return null;
            }

            // Apply configuration
            if (method_exists($middleware, 'setOnly') && isset($item['only'])) {
                $middleware->setOnly((array) $item['only']);
            }

            if (method_exists($middleware, 'setExcept') && isset($item['except'])) {
                $middleware->setExcept((array) $item['except']);
            }

            return $middleware;
        }

        return null;
    }

    /**
     * Create middleware stack.
     *
     * @param array<MiddlewareInterface> $middleware Middleware instances
     * @param callable $next Next handler
     * @return MiddlewareStack
     */
    protected function createStack(array $middleware, callable $next): MiddlewareStack
    {
        $stack = new MiddlewareStack();

        // Set fallback handler
        $handler = new class ($next, $this->responseFactory) implements RequestHandlerInterface {
            /** @var callable */
            private $next;

            /** @var ResponseFactoryInterface */
            private ResponseFactoryInterface $factory;

            public function __construct(callable $next, ResponseFactoryInterface $factory)
            {
                $this->next = $next;
                $this->factory = $factory;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $result = ($this->next)($request);

                if ($result instanceof ResponseInterface) {
                    return $result;
                }

                // Convert string to response
                if (is_string($result)) {
                    return $this->factory->createResponse()
                        ->withHeader('Content-Type', 'text/html; charset=utf-8')
                        ->withBody($this->factory->createStream($result));
                }

                return $this->factory->createResponse();
            }
        };

        $stack->setFallbackHandler($handler);
        $stack->addMany($middleware);

        return $stack;
    }

    /**
     * Get resolver.
     *
     * @return MiddlewareResolver
     */
    public function getResolver(): MiddlewareResolver
    {
        return $this->resolver;
    }

    /**
     * Set resolver.
     *
     * @param MiddlewareResolver $resolver Resolver instance
     * @return self
     */
    public function setResolver(MiddlewareResolver $resolver): self
    {
        $this->resolver = $resolver;
        return $this;
    }
}
