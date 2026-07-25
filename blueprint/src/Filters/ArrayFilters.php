<?php

declare(strict_types=1);

namespace Blueprint\Engine\Filters;

/**
 * Array Filters
 * 
 * Filters for array manipulation.
 * 
 * @package Blueprint\Engine\Filters
 */
class ArrayFilters
{
    /**
     * Get all array filters
     * 
     * @return array<string, callable>
     */
    public static function getFilters(): array
    {
        return [
            'join' => [self::class, 'join'],
            'length' => [self::class, 'length'],
            'count' => [self::class, 'length'],
            'first' => [self::class, 'first'],
            'last' => [self::class, 'last'],
            'keys' => [self::class, 'keys'],
            'values' => [self::class, 'values'],
            'reverse' => [self::class, 'reverse'],
            'sort' => [self::class, 'sort'],
            'ksort' => [self::class, 'ksort'],
            'shuffle' => [self::class, 'shuffle'],
            'merge' => [self::class, 'merge'],
            'slice' => [self::class, 'slice'],
            'map' => [self::class, 'map'],
            'filter' => [self::class, 'filter'],
            'reduce' => [self::class, 'reduce'],
            'column' => [self::class, 'column'],
        ];
    }

    /**
     * Join array elements
     */
    public static function join(mixed $value, mixed $separator = ', '): string
    {
        if (!is_array($value)) {
            return (string) $value;
        }
        return implode((string) $separator, $value);
    }

    /**
     * Get length/count
     */
    public static function length(mixed $value): int
    {
        if (is_array($value)) {
            return count($value);
        }
        if (is_string($value)) {
            return mb_strlen($value, 'UTF-8');
        }
        if (is_countable($value)) {
            return count($value);
        }
        return 0;
    }

    /**
     * Get first element
     */
    public static function first(mixed $value): mixed
    {
        if (is_array($value) && !empty($value)) {
            return reset($value);
        }
        return null;
    }

    /**
     * Get last element
     */
    public static function last(mixed $value): mixed
    {
        if (is_array($value) && !empty($value)) {
            return end($value);
        }
        return null;
    }

    /**
     * Get array keys
     */
    public static function keys(mixed $value): array
    {
        if (is_array($value)) {
            return array_keys($value);
        }
        return [];
    }

    /**
     * Get array values
     */
    public static function values(mixed $value): array
    {
        if (is_array($value)) {
            return array_values($value);
        }
        return [];
    }

    /**
     * Reverse array
     */
    public static function reverse(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_reverse($value);
        }
        if (is_string($value)) {
            return strrev($value);
        }
        return $value;
    }

    /**
     * Sort array
     */
    public static function sort(mixed $value): array
    {
        if (is_array($value)) {
            $sorted = $value;
            sort($sorted);
            return $sorted;
        }
        return [];
    }

    /**
     * Sort array by keys
     */
    public static function ksort(mixed $value): array
    {
        if (is_array($value)) {
            $sorted = $value;
            ksort($sorted);
            return $sorted;
        }
        return [];
    }

    /**
     * Shuffle array
     */
    public static function shuffle(mixed $value): array
    {
        if (is_array($value)) {
            $shuffled = $value;
            shuffle($shuffled);
            return $shuffled;
        }
        return [];
    }

    /**
     * Merge arrays
     */
    public static function merge(mixed $value, array ...$arrays): array
    {
        if (!is_array($value)) {
            $value = [$value];
        }
        return array_merge($value, ...$arrays);
    }

    /**
     * Slice array
     */
    public static function slice(mixed $value, mixed $offset, mixed $length = null): array
    {
        if (!is_array($value)) {
            return [];
        }
        return array_slice($value, (int) $offset, $length !== null ? (int) $length : null);
    }

    /**
     * Map array
     */
    public static function map(array $value, callable $callback): array
    {
        return array_map($callback, $value);
    }

    /**
     * Filter array
     */
    public static function filter(array $value, ?callable $callback = null): array
    {
        return array_filter($value, $callback ?? fn($v) => !empty($v));
    }

    /**
     * Reduce array
     */
    public static function reduce(array $value, callable $callback, mixed $initial = null): mixed
    {
        return array_reduce($value, $callback, $initial);
    }

    /**
     * Get column from array of arrays/objects
     */
    public static function column(array $value, string $column): array
    {
        return array_column($value, $column);
    }
}
