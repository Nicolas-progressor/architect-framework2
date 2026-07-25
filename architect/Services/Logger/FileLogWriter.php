<?php

declare(strict_types=1);

namespace Architect\Services\Logger;

/**
 * File-based log writer.
 *
 * Writes log entries to files, organized by channel and date.
 */
final class FileLogWriter implements LogWriterInterface
{
    private bool $dirCreated = false;

    public function __construct(
        private readonly LoggerConfig $config
    ) {}

    /**
     * Write log entries to files.
     */
    public function write(array $entries): void
    {
        if (empty($entries)) {
            return;
        }

        $this->ensureLogDirectory();

        $grouped = $this->groupEntriesByChannel($entries);

        foreach ($grouped as $channel => $channelEntries) {
            $this->writeChannelFile($channel, $channelEntries);
        }
    }

    /**
     * Flush (no-op for file writer as we write immediately).
     */
    public function flush(): void
    {
        // File writer doesn't buffer, so this is a no-op
    }

    /**
     * Group entries by channel.
     */
    private function groupEntriesByChannel(array $entries): array
    {
        $grouped = [];
        foreach ($entries as $entry) {
            $channel = $entry['channel'] ?? 'app';
            $grouped[$channel][] = $entry;
        }
        return $grouped;
    }

    /**
     * Write entries for a specific channel.
     */
    private function writeChannelFile(string $channel, array $entries): void
    {
        $filename = $this->getFilename($channel);
        $lines = $this->formatEntries($entries);

        $result = file_put_contents(
            $filename,
            implode(PHP_EOL, $lines) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );

        if ($result === false) {
            // Log to error_log as fallback (avoid infinite loop with Logger)
            error_log(sprintf(
                'Logger: Failed to write to %s',
                $filename
            ));
        }

        $this->rotateIfNeeded($filename);
    }

    /**
     * Format entries as log lines.
     */
    private function formatEntries(array $entries): array
    {
        $lines = [];

        foreach ($entries as $entry) {
            $line = sprintf(
                '[%s] [%s] %s',
                $entry['time'],
                strtoupper($entry['level']),
                $entry['message']
            );

            if ($this->config->includeContext && !empty($entry['context'])) {
                $context = $this->formatContext($entry['context']);
                if ($context !== '') {
                    $line .= ' ' . $context;
                }
            }

            $lines[] = $line;
        }

        return $lines;
    }

    /**
     * Format context as string.
     */
    private function formatContext(array $context): string
    {
        if (empty($context)) {
            return '';
        }

        try {
            $encoded = json_encode(
                $context,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
            return $encoded !== false ? '[' . $encoded . ']' : '';
        } catch (\JsonException) {
            return '';
        }
    }

    /**
     * Get filename for channel.
     */
    private function getFilename(string $channel): string
    {
        $date = date('Y-m-d');
        $filename = str_replace(
            ['{channel}', '{date}'],
            [$channel, $date],
            $this->config->filenamePattern
        );

        return $this->config->logDir . $filename;
    }

    /**
     * Ensure log directory exists.
     */
    private function ensureLogDirectory(): void
    {
        if ($this->dirCreated) {
            return;
        }

        if (!is_dir($this->config->logDir)) {
            if (!@mkdir($this->config->logDir, $this->config->filePermissions, true)) {
                $error = error_get_last();
                throw new \RuntimeException(sprintf(
                    'Cannot create log directory "%s": %s',
                    $this->config->logDir,
                    $error['message'] ?? 'Unknown error'
                ));
            }
        }

        $this->dirCreated = true;
    }

    /**
     * Rotate log file if it exceeds max size.
     */
    private function rotateIfNeeded(string $filename): void
    {
        if ($this->config->maxFileSize <= 0) {
            return;
        }

        if (!file_exists($filename)) {
            return;
        }

        $size = filesize($filename);
        if ($size === false || $size < $this->config->maxFileSize) {
            return;
        }

        $this->rotateFile($filename);
    }

    /**
     * Rotate log file.
     */
    private function rotateFile(string $filename): void
    {
        $backup = $filename . '.' . date('His');

        if (!@rename($filename, $backup)) {
            error_log(sprintf('Logger: Failed to rotate %s', $filename));
            return;
        }

        // Clean old files if maxFiles is set
        if ($this->config->maxFiles > 0) {
            $this->cleanOldFiles($filename);
        }
    }

    /**
     * Clean old log files.
     */
    private function cleanOldFiles(string $filename): void
    {
        $pattern = $filename . '.*';
        $files = glob($pattern);

        if ($files === false || count($files) <= $this->config->maxFiles) {
            return;
        }

        // Sort by modification time (oldest first)
        usort($files, function ($a, $b) {
            return filemtime($a) <=> filemtime($b);
        });

        // Remove oldest files
        $toRemove = count($files) - $this->config->maxFiles;
        for ($i = 0; $i < $toRemove; $i++) {
            @unlink($files[$i]);
        }
    }
}
