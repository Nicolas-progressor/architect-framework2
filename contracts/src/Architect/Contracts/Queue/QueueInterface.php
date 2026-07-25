<?php

declare(strict_types=1);

namespace Architect\Contracts\Queue;

interface QueueInterface
{
    public function push(object $job, string $queue = 'default'): void;
    public function later(int $delay, object $job, string $queue = 'default'): void;
    public function pop(string $queue = 'default'): ?object;
    public function size(string $queue = 'default'): int;
}
