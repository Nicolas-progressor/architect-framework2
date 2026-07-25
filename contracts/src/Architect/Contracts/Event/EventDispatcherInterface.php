<?php

declare(strict_types=1);

namespace Architect\Contracts\Event;

interface EventDispatcherInterface
{
    public function listen(string $event, callable $listener, int $priority = 0): void;
    public function dispatch(string $event, mixed $payload = null): mixed;
    public function hasListeners(string $event): bool;
}
