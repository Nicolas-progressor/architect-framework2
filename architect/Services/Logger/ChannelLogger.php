<?php

declare(strict_types=1);

namespace Architect\Services\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

/**
 * Channel-specific logger proxy.
 * 
 * Allows logging to a specific channel without specifying it each time.
 * 
 * @example
 *   $logger->channel('api')->info('Request received');
 */
final class ChannelLogger implements LoggerInterface
{
    use LoggerTrait;

    public function __construct(
        private readonly Logger $logger,
        private readonly string $channel
    ) {}

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = []): void
    {
        $this->logger->logWithChannel((string) $level, $message, $context, $this->channel);
    }
}
