<?php

declare(strict_types=1);

namespace Architect\Helpers\ArrayHelper;

use Architect\Helpers\Core\AbstractHelper;

/**
 * Array helper functions (inspired by Laravel).
 */
class ArrayHelper extends AbstractHelper
{
    /**
     * Get an item from an array using "dot" notation.
     */
    public static function get(array $array, string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        if (!str_contains($key, '.')) {
            return $default;
        }

        foreach (explode('.', $key) as $segment) {
            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }

        return $array;
    }

    /**
     * Set an array item to a given value using "dot" notation.
     */
    public static function set(array &$array, string $key, mixed $value): array
    {
        $keys = explode('.', $key);
        $current = &$array;

        foreach ($keys as $i => $keyPart) {
            if (count($keys) === 1) {
                break;
            }

            unset($keys[$i]);

            if (!isset($current[$keyPart]) || !is_array($current[$keyPart])) {
                $current[$keyPart] = [];
            }

            $current = &$current[$keyPart];
        }

        $current[array_shift($keys)] = $value;

        return $array;
    }

    /**
     * Check if an item exists in an array using "dot" notation.
     */
    public static function has(array $array, string $key): bool
    {
        if (array_key_exists($key, $array)) {
            return true;
        }

        if (!str_contains($key, '.')) {
            return false;
        }

        $segments = explode('.', $key);
        foreach ($segments as $segment) {
            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the first element of an array passing a given truth test.
     */
    public static function first(array $array, ?callable $callback = null, mixed $default = null): mixed
    {
        if (is_null($callback)) {
            if (empty($array)) {
                return $default;
            }
            return reset($array);
        }

        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return $default;
    }

    /**
     * Get the last element of an array passing a given truth test.
     */
    public static function last(array $array, ?callable $callback = null, mixed $default = null): mixed
    {
        if (is_null($callback)) {
            if (empty($array)) {
                return $default;
            }
            return end($array);
        }

        $reversed = array_reverse($array, true);
        foreach ($reversed as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return $default;
    }

    /**
     * Pluck an array of values from an array.
     */
    public static function pluck(array $array, string $value, ?string $key = null): array
    {
        $results = [];

        foreach ($array as $item) {
            if (is_array($item) && array_key_exists($value, $item)) {
                $itemValue = $item[$value];
                if ($key !== null && array_key_exists($key, $item)) {
                    $results[$item[$key]] = $itemValue;
                } else {
                    $results[] = $itemValue;
                }
            }
        }

        return $results;
    }

    /**
     * Return only the specified keys from the array.
     */
    public static function only(array $array, array $keys): array
    {
        return array_intersect_key($array, array_flip($keys));
    }

    /**
     * Return all array elements except for those with the specified keys.
     */
    public static function except(array $array, array $keys): array
    {
        return array_diff_key($array, array_flip($keys));
    }

    /**
     * Collapse an array of arrays into a single array.
     */
    public static function collapse(array $array): array
    {
        $results = [];
        foreach ($array as $values) {
            if (!is_array($values)) {
                continue;
            }
            $results = array_merge($results, $values);
        }
        return $results;
    }

    /**
     * Flatten a multi-dimensional associative array with dots.
     */
    public static function dot(array $array, string $prepend = ''): array
    {
        $results = [];

        foreach ($array as $key => $value) {
            if (is_array($value) && !empty($value)) {
                $results = array_merge($results, self::dot($value, $prepend . $key . '.'));
            } else {
                $results[$prepend . $key] = $value;
            }
        }

        return $results;
    }

    /**
     * Convert a flattened "dot" notation array into an expanded array.
     */
    public static function undot(array $array): array
    {
        $results = [];

        foreach ($array as $key => $value) {
            self::set($results, $key, $value);
        }

        return $results;
    }

    /**
     * Wrap the given value in an array if it is not already an array.
     */
    public static function wrap(mixed $value): array
    {
        if (is_null($value)) {
            return [];
        }

        return is_array($value) ? $value : [$value];
    }
}
