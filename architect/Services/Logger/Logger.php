<?php

declare(strict_types=1);

namespace Architect\Services\Logger;

use Architect\Services\Logger\Contracts\LoggerInterface;
use Architect\Support\AbstractService;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;

/**
 * PSR-3 compliant Logger service.
 *
 * Features:
 * - Full PSR-3 compatibility
 * - Channel-based logging
 * - Buffered writes with configurable flush limit
 * - Optional Debug service integration (without circular dependency)
 * - Configurable log levels and formatting
 */
class Logger extends AbstractService implements LoggerInterface
{
    /**
     * Log level priorities (higher = more severe).
     */
    private const LEVEL_PRIORITY = [
        LogLevel::DEBUG     => 0,
        LogLevel::INFO      => 1,
        LogLevel::NOTICE    => 2,
        LogLevel::WARNING   => 3,
        LogLevel::ERROR     => 4,
        LogLevel::CRITICAL  => 5,
        LogLevel::ALERT     => 6,
        LogLevel::EMERGENCY => 7,
    ];

    /** @var array<int, array> Log entry buffer */
    private array $queue = [];

    /** @var string Default channel */
    private string $channel = 'app';

    /** @var LoggerConfig Configuration */
    private LoggerConfig $config;

    /** @var LogWriterInterface|null Log writer */
    private ?LogWriterInterface $writer = null;

    /** @var callable|null Debug callback (set via setDebugCallback) */
    private $debugCallback = null;

    /** @var bool Flag to prevent recursion in debug callback */
    private bool $inDebugCallback = false;

    /**
     * Create logger instance.
     */
    public function __construct(
        \Architect\Core\Contracts\ContainerInterface $container,
        ?LoggerConfig $config = null,
        ?LogWriterInterface $writer = null
    ) {
        parent::__construct($container);

        $this->config = $config ?? LoggerConfig::default();
        $this->writer = $writer ?? new FileLogWriter($this->config);
    }

    /**
     * Boot the service.
     */
    public function boot(): void
    {
        // Register shutdown function for guaranteed flush
        register_shutdown_function([$this, 'flush']);
    }

    /**
     * Set debug callback for integration with Debug service.
     *
     * This avoids circular dependency by using a callback.
     * Call this from Debug service boot.
     */
    public function setDebugCallback(callable $callback): void
    {
        $this->debugCallback = $callback;
    }

    // ============================================
    // PSR-3 LoggerInterface Implementation
    // ============================================

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = []): void
    {
        $level = (string) $level;
        $this->validateLevel($level);

        $this->writeLog(
            $level,
            $this->formatMessage($message, $context),
            $context,
            $this->channel
        );
    }

    /**
     * Log with specific channel.
     */
    public function logWithChannel(
        string $level,
        $message,
        array $context = [],
        string $channel = 'app'
    ): void {
        $this->validateLevel($level);

        $this->writeLog(
            $level,
            $this->formatMessage($message, $context),
            $context,
            $channel
        );
    }

    /**
     * {@inheritdoc}
     */
    public function emergency($message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function alert($message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function critical($message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function error($message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function warning($message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function notice($message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function info($message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function debug($message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    // ============================================
    // Channel Management
    // ============================================

    /**
     * Get current default channel.
     */
    public function getChannel(): string
    {
        return $this->channel;
    }

    /**
     * Set default channel.
     */
    public function setChannel(string $channel): void
    {
        $this->channel = $channel;
    }

    /**
     * Create a channel-specific logger.
     */
    public function channel(string $channel): ChannelLogger
    {
        return new ChannelLogger($this, $channel);
    }

    // ============================================
    // Buffer Management
    // ============================================

    /**
     * Flush buffered log entries.
     */
    public function flush(): void
    {
        if (empty($this->queue)) {
            return;
        }

        $entries = $this->queue;
        $this->queue = [];

        $this->writer->write($entries);
    }

    // ============================================
    // Internal Methods
    // ============================================

    /**
     * Write log entry.
     */
    private function writeLog(string $level, string $message, array $context, string $channel): void
    {
        // Check minimum level
        if (!$this->shouldLog($level)) {
            return;
        }

        $entry = [
            'time' => date($this->config->dateFormat),
            'timestamp' => microtime(true),
            'level' => $level,
            'channel' => $channel,
            'message' => $message,
            'context' => $context,
        ];

        $this->queue[] = $entry;

        // Send to debug callback (if set and not in recursion)
        $this->sendToDebug($entry);

        // Flush based on conditions
        $this->maybeFlush($channel);
    }

    /**
     * Check if level should be logged based on minimum level.
     */
    private function shouldLog(string $level): bool
    {
        $minPriority = self::LEVEL_PRIORITY[$this->config->minLevel] ?? 0;
        $levelPriority = self::LEVEL_PRIORITY[$level] ?? 0;

        return $levelPriority >= $minPriority;
    }

    /**
     * Format message with context interpolation.
     */
    private function formatMessage($message, array $context): string
    {
        $message = (string) $message;

        // PSR-3 context interpolation: {key} replaced with value
        if (strpos($message, '{') === false) {
            return $message;
        }

        $replacements = [];
        foreach ($context as $key => $value) {
            // Skip non-scalar values for interpolation
            if (is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
                $replacements['{' . $key . '}'] = (string) $value;
            }
        }

        return strtr($message, $replacements);
    }

    /**
     * Send entry to debug callback.
     */
    private function sendToDebug(array $entry): void
    {
        // Prevent recursion
        if ($this->inDebugCallback) {
            return;
        }

        if ($this->debugCallback === null) {
            return;
        }

        try {
            $this->inDebugCallback = true;
            ($this->debugCallback)($entry);
        } catch (\Throwable) {
            // Silently ignore debug callback errors
        } finally {
            $this->inDebugCallback = false;
        }
    }

    /**
     * Determine if flush is needed.
     */
    private function maybeFlush(string $channel): void
    {
        // Immediate flush for critical channels
        if (in_array($channel, ['debug', 'system', 'emergency'], true)) {
            $this->flush();
            return;
        }

        // Flush when buffer is full
        if (count($this->queue) >= $this->config->flushLimit) {
            $this->flush();
        }
    }

    /**
     * Validate log level.
     */
    private function validateLevel(string $level): void
    {
        if (!isset(self::LEVEL_PRIORITY[$level])) {
            throw new InvalidArgumentException(sprintf(
                'Invalid log level "%s". Expected one of: %s',
                $level,
                implode(', ', array_keys(self::LEVEL_PRIORITY))
            ));
        }
    }

    /**
     * Destructor - ensure flush on shutdown.
     */
    public function __destruct()
    {
        $this->flush();
    }
}
