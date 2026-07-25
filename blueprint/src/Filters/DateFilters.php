<?php

declare(strict_types=1);

namespace Blueprint\Engine\Filters;

/**
 * Date Filters
 * 
 * Filters for date/time manipulation.
 * 
 * @package Blueprint\Engine\Filters
 */
class DateFilters
{
    /**
     * Get all date filters
     * 
     * @return array<string, callable>
     */
    public static function getFilters(): array
    {
        return [
            'date' => [self::class, 'date'],
            'format_date' => [self::class, 'date'],
            'time_ago' => [self::class, 'timeAgo'],
            'strtotime' => [self::class, 'strtotime'],
        ];
    }

    /**
     * Format date
     */
    public static function date(mixed $value, mixed $format = 'd.m.Y'): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $timestamp = $value;

        // If string, try to parse
        if (is_string($value)) {
            $timestamp = strtotime($value);
            if ($timestamp === false) {
                return (string) $value;
            }
        }

        // If DateTime object
        if ($value instanceof \DateTimeInterface) {
            return $value->format((string) $format);
        }

        return date((string) $format, (int) $timestamp);
    }

    /**
     * Time ago format
     */
    public static function timeAgo(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $timestamp = is_numeric($value) ? (int) $value : strtotime((string) $value);
        if ($timestamp === false) {
            return (string) $value;
        }

        $diff = time() - $timestamp;

        if ($diff < 60) {
            return 'just now';
        }
        if ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
        }
        if ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        }
        if ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        }
        if ($diff < 2592000) {
            $weeks = floor($diff / 604800);
            return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
        }
        if ($diff < 31536000) {
            $months = floor($diff / 2592000);
            return $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
        }

        $years = floor($diff / 31536000);
        return $years . ' year' . ($years > 1 ? 's' : '') . ' ago';
    }

    /**
     * Convert to timestamp
     */
    public static function strtotime(mixed $value): int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        $result = strtotime((string) $value);
        return $result !== false ? $result : 0;
    }
}
