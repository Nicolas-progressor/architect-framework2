<?php

declare(strict_types=1);

namespace Architect\Contracts\Core;

use Psr\Container\ContainerInterface as PsrContainerInterface;

interface ContainerInterface extends PsrContainerInterface
{
    public function set(string $id, mixed $concrete): void;
    public function factory(string $id, callable $factory): void;
    public function bind(string $id, string $concrete): void;
    public function afterResolving(string $id, callable $callback): void;
    public function reset(): void;
}
