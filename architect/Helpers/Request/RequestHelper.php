<?php

declare(strict_types=1);

namespace Architect\Helpers\Request;

use Architect\Contracts\Core\ContainerInterface;
use Architect\Helpers\Core\AbstractHelper;

/**
 * Request helper for accessing request parameters.
 */
class RequestHelper extends AbstractHelper
{
    private ContainerInterface $container;

    /**
     * Create Request helper with container.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Get GET parameter.
     */
    public function get(string $name): mixed
    {
        return $_GET[$name] ?? null;
    }

    /**
     * Get POST parameter.
     */
    public function post(string $name): mixed
    {
        return $_POST[$name] ?? null;
    }

    /**
     * Get Clean URL parameter.
     */
    public function cpu(int $number): mixed
    {
        try {
            if (!$this->container->has('router')) {
                return null;
            }

            $router = $this->container->get('router');
            return $router->rules->segVar[$number] ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Get all parameters (GET + POST + CPU).
     */
    public function all(): array
    {
        $args = [];

        if (!empty($_POST)) {
            $args = array_merge($args, $_POST);
        }
        if (!empty($_GET)) {
            $args = array_merge($args, $_GET);
        }

        try {
            if ($this->container->has('router')) {
                $router = $this->container->get('router');
                if (isset($router->rules->segVar)) {
                    foreach ($router->rules->segVar as $key => $val) {
                        $args['cpu' . $key] = $val;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Ignore
        }

        return $args;
    }
}
