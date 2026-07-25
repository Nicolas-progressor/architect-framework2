<?php

declare(strict_types=1);

namespace Architect\Services\Logger;

/**
 * Configuration DTO for Logger service.
 */
final class LoggerConfig
{
    public function __construct(
        public readonly string $logDir,
        public readonly int $flushLimit = 10,
        public readonly string $minLevel = 'debug',
        public readonly string $dateFormat = 'Y-m-d H:i:s',
        public readonly string $filenamePattern = '{channel}_{date}.log',
        public readonly bool $includeContext = true,
        public readonly int $filePermissions = 0o755,
        public readonly int $maxFileSize = 0, // 0 = no limit
        public readonly int $maxFiles = 0,    // 0 = no limit
    ) {}

    /**
     * Create config from array.
     */
    public static function fromArray(array $config): self
    {
        $logDir = $config['log_dir'] ?? (
            defined('APP_DIR')
                ? APP_DIR . 'logs/'
                : dirname(__DIR__, 3) . '/app/logs/'
        );

        return new self(
            logDir: $logDir,
            flushLimit: (int) ($config['flush_limit'] ?? 10),
            minLevel: $config['min_level'] ?? 'debug',
            dateFormat: $config['date_format'] ?? 'Y-m-d H:i:s',
            filenamePattern: $config['filename_pattern'] ?? '{channel}_{date}.log',
            includeContext: (bool) ($config['include_context'] ?? true),
            filePermissions: (int) ($config['file_permissions'] ?? 0o755),
            maxFileSize: (int) ($config['max_file_size'] ?? 0),
            maxFiles: (int) ($config['max_files'] ?? 0),
        );
    }

    /**
     * Default configuration.
     */
    public static function default(): self
    {
        $logDir = defined('APP_DIR')
            ? APP_DIR . 'logs/'
            : dirname(__DIR__, 3) . '/app/logs/';

        return new self(logDir: $logDir);
    }
}
