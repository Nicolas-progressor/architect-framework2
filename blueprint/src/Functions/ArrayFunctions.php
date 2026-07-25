<?php

declare(strict_types=1);

namespace Blueprint\Engine\Functions;

/**
 * Array Functions
 * 
 * Functions for array manipulation.
 * 
 * @package Blueprint\Engine\Functions
 */
class ArrayFunctions
{
    /**
     * Get all array functions
     * 
     * @return array<string, callable>
     */
    public static function getFunctions(): array
    {
        return [
            'array' => [self::class, 'toArray'],
            'range' => [self::class, 'range'],
            'keys' => [self::class, 'keys'],
            'values' => [self::class, 'values'],
            'merge' => [self::class, 'merge'],
            'pluck' => [self::class, 'pluck'],
            'flip' => [self::class, 'flip'],
            'chunk' => [self::class, 'chunk'],
            'combine' => [self::class, 'combine'],
            'fill' => [self::class, 'fill'],
            'pad' => [self::class, 'pad'],
            'pop' => [self::class, 'pop'],
            'push' => [self::class, 'push'],
            'shift' => [self::class, 'shift'],
            'unshift' => [self::class, 'unshift'],
            'slice' => [self::class, 'slice'],
            'splice' => [self::class, 'splice'],
            'unique' => [self::class, 'unique'],
            'diff' => [self::class, 'diff'],
            'intersect' => [self::class, 'intersect'],
        ];
    }

    /**
     * Convert to array
     */
    public static function toArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if ($value instanceof \Traversable) {
            return iterator_to_array($value);
        }
        return [$value];
    }

    /**
     * Create range
     */
    public static function range(mixed $start, mixed $end, int $step = 1): array
    {
        return range($start, $end, $step);
    }

    /**
     * Get keys
     */
    public static function keys(array $array): array
    {
        return array_keys($array);
    }

    /**
     * Get values
     */
    public static function values(array $array): array
    {
        return array_values($array);
    }

    /**
     * Merge arrays
     */
    public static function merge(array ...$arrays): array
    {
        return array_merge(...$arrays);
    }

    /**
     * Pluck values by key
     */
    public static function pluck(array $array, string $key): array
    {
        return array_column($array, $key);
    }

    /**
     * Flip array
     */
    public static function flip(array $array): array
    {
        return array_flip($array);
    }

    /**
     * Chunk array
     */
    public static function chunk(array $array, int $size): array
    {
        return array_chunk($array, $size);
    }

    /**
     * Combine keys and values
     */
    public static function combine(array $keys, array $values): array
    {
        return array_combine($keys, $values) ?: [];
    }

    /**
     * Fill array
     */
    public static function fill(int $start, int $count, mixed $value): array
    {
        return array_fill($start, $count, $value);
    }

    /**
     * Pad array
     */
    public static function pad(array $array, int $size, mixed $value): array
    {
        return array_pad($array, $size, $value);
    }

    /**
     * Pop last element
     */
    public static function pop(array &$array): mixed
    {
        return array_pop($array);
    }

    /**
     * Push elements
     */
    public static function push(array &$array, mixed ...$values): int
    {
        return array_push($array, ...$values);
    }

    /**
     * Shift first element
     */
    public static function shift(array &$array): mixed
    {
        return array_shift($array);
    }

    /**
     * Unshift elements
     */
    public static function unshift(array &$array, mixed ...$values): int
    {
        return array_unshift($array, ...$values);
    }

    /**
     * Slice array
     */
    public static function slice(array $array, int $offset, ?int $length = null): array
    {
        return array_slice($array, $offset, $length);
    }

    /**
     * Splice array
     */
    public static function splice(array &$array, int $offset, ?int $length = null, mixed $replacement = []): array
    {
        return array_splice($array, $offset, $length, $replacement);
    }

    /**
     * Unique values
     */
    public static function unique(array $array): array
    {
        return array_unique($array);
    }

    /**
     * Array difference
     */
    public static function diff(array $array, array ...$arrays): array
    {
        return array_diff($array, ...$arrays);
    }

    /**
     * Array intersection
     */
    public static function intersect(array $array, array ...$arrays): array
    {
        return array_intersect($array, ...$arrays);
    }
}
