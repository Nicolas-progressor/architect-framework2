<?php

declare(strict_types=1);

namespace Architect\Services\Config;

use RuntimeException;

/**
 * Exception thrown when configuration loading fails.
 */
final class ConfigLoadException extends RuntimeException
{
    /**
     * Create configuration load exception.
     * 
     * @param string $message Error message
     * @param string $configName Configuration name
     * @param string|null $filePath File path that caused the error
     * @param int $code Error code
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message,
        private readonly string $configName,
        private readonly ?string $filePath = null,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the configuration name that failed to load.
     * 
     * @return string
     */
    public function getConfigName(): string
    {
        return $this->configName;
    }

    /**
     * Get the file path that caused the error.
     * 
     * @return string|null
     */
    public function getFilePath(): ?string
    {
        return $this->filePath;
    }
}
