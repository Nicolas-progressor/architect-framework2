<?php

declare(strict_types=1);

namespace Architect\Services\Logger;

/**
 * Interface for log writers.
 *
 * Writers are responsible for persisting log entries.
 */
interface LogWriterInterface
{
    /**
     * Write log entries to storage.
     *
     * @param array<int, array{time: string, timestamp: float, level: string, channel: string, message: string, context: array}> $entries
     */
    public function write(array $entries): void;

    /**
     * Flush any buffered data.
     */
    public function flush(): void;
}
