<?php

declare(strict_types=1);

namespace Architect\Services\Debug\Traits;

/**
 * Provides common formatting utilities for debug components.
 */
trait DebugFormatterTrait
{
    private const MAX_DATA_SIZE = 1048576;
    private const MAX_DEPTH = 5;

    /**
     * Formats bytes to human-readable string.
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return round($bytes / 1048576, 1) . ' MB';
    }

    /**
     * Converts memory limit string to bytes.
     */
    protected function parseMemoryLimit(string $limit): int
    {
        if ($limit === '-1') {
            return 0;
        }

        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $value = (int) $limit;

        match ($last) {
            'g' => $value *= 1024,
            'm' => $value *= 1024,
            'k' => $value *= 1024,
            default => $value,
        };

        return $value;
    }

    /**
     * Truncates data if it exceeds size limit.
     */
    protected function truncateData(array $context): array
    {
        $dataSize = strlen(serialize($context));

        if ($dataSize > self::MAX_DATA_SIZE) {
            return ['_truncated' => true, 'size' => $dataSize];
        }

        return $context;
    }

    /**
     * Flattens nested data to max depth.
     */
    protected function flattenData($data, int $depth = 0)
    {
        if ($depth >= self::MAX_DEPTH) {
            return ['_max_depth_reached' => true];
        }

        if (is_array($data)) {
            $result = [];
            foreach ($data as $key => $value) {
                $result[$key] = $this->flattenData($value, $depth + 1);
            }
            return $result;
        }

        return $data;
    }

    /**
     * Sanitizes sensitive data by masking it.
     */
    protected function sanitizeData(array $data): array
    {
        $sensitiveKeys = [
            'password', 'passwd', 'pwd', 'secret', 'token', 'api_key', 'apikey',
            'access_token', 'refresh_token', 'authorization', 'credit_card',
            'card_number', 'cvv', 'ssn', 'email',
        ];

        return $this->processSensitiveData($data, $sensitiveKeys);
    }

    /**
     * Recursively processes sensitive data.
     */
    private function processSensitiveData(array $data, array $sensitiveKeys): array
    {
        foreach ($data as $key => $value) {
            if (!is_string($key)) {
                if (is_array($value)) {
                    $data[$key] = $this->processSensitiveData($value, $sensitiveKeys);
                }
                continue;
            }

            $lowerKey = strtolower($key);

            if (in_array($lowerKey, $sensitiveKeys, true)) {
                $data[$key] = '***MASKED***';
            } elseif (is_array($value)) {
                $data[$key] = $this->processSensitiveData($value, $sensitiveKeys);
            }
        }

        return $data;
    }
}
