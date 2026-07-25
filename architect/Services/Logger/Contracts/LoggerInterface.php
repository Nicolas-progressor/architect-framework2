<?php

declare(strict_types=1);

namespace Architect\Services\Logger\Contracts;

use Psr\Log\LoggerInterface as PsrLoggerInterface;

/**
 * Extended interface for Logger service.
 * 
 * Combines PSR-3 standard with Architect-specific features.
 */
interface LoggerInterface extends PsrLoggerInterface
{
    /**
     * Log with channel support.
     * 
     * @param string $level Log level (emergency|alert|critical|error|warning|notice|info|debug)
     * @param string|\Stringable $message Log message
     * @param array $context Additional context data
     * @param string $channel Log channel (app, system, debug, etc.)
     */
    public function logWithChannel(
        string $level, 
        $message, 
        array $context = [], 
        string $channel = 'app'
    ): void;

    /**
     * Flush buffered log entries.
     */
    public function flush(): void;

    /**
     * Get current channel.
     */
    public function getChannel(): string;

    /**
     * Set default channel.
     */
    public function setChannel(string $channel): void;
}
