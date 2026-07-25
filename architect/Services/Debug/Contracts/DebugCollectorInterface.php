<?php

declare(strict_types=1);

namespace Architect\Services\Debug\Contracts;

/**
 * Interface for debug data collectors.
 */
interface DebugCollectorInterface
{
    public function addMessage(string $category, string $message, string $level = 'info', array $context = []): void;
    public function startTimer(string $name, string $category = 'performance'): void;
    public function stopTimer(string $name): ?float;
    public function addData(string $category, $data, string $description = ''): void;
    public function incrementCounter(string $category, string $counterName, int $value = 1): void;
    public function markEvent(string $eventName, array $metadata = []): void;
    public function setMetadata(string $key, $value): void;
    public function getData(): array;
    public function clear(): void;
}
