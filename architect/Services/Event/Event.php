<?php

declare(strict_types=1);

namespace Architect\Services\Event;

/**
 * Base event class with propagation control.
 */
class Event
{
    private string $name;
    private mixed $payload = null;
    private bool $propagationStopped = false;

    public function __construct(string $name, mixed $payload = null)
    {
        $this->name = $name;
        $this->payload = $payload;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPayload(): mixed
    {
        return $this->payload;
    }

    public function setPayload(mixed $payload): void
    {
        $this->payload = $payload;
    }

    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }
}
