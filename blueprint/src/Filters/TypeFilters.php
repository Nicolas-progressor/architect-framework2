<?php

declare(strict_types=1);

namespace Blueprint\Engine\Filters;

/**
 * Type Filters
 * 
 * Filters for type checking and default values.
 * 
 * @package Blueprint\Engine\Filters
 */
class TypeFilters
{
    /**
     * Get all type filters
     * 
     * @return array<string, callable>
     */
    public static function getFilters(): array
    {
        return [
            'default' => [self::class, 'defaultFilter'],
            'default_to' => [self::class, 'defaultFilter'],
            'empty' => [self::class, 'isEmpty'],
            'null' => [self::class, 'isNull'],
            'numeric' => [self::class, 'isNumeric'],
            'integer' => [self::class, 'isInteger'],
            'int' => [self::class, 'isInteger'],
            'string' => [self::class, 'isString'],
            'array' => [self::class, 'isArray'],
            'object' => [self::class, 'isObject'],
            'bool' => [self::class, 'isBool'],
            'boolean' => [self::class, 'isBool'],
            'callable' => [self::class, 'isCallable'],
            'iterable' => [self::class, 'isIterable'],
        ];
    }

    /**
     * Default value if empty/null
     */
    public static function defaultFilter(mixed $value, mixed $default = ''): mixed
    {
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            return $default;
        }
        return $value;
    }

    /**
     * Check if empty
     */
    public static function isEmpty(mixed $value): bool
    {
        return empty($value);
    }

    /**
     * Check if null
     */
    public static function isNull(mixed $value): bool
    {
        return $value === null;
    }

    /**
     * Check if numeric
     */
    public static function isNumeric(mixed $value): bool
    {
        return is_numeric($value);
    }

    /**
     * Check if integer
     */
    public static function isInteger(mixed $value): bool
    {
        return is_int($value);
    }

    /**
     * Check if string
     */
    public static function isString(mixed $value): bool
    {
        return is_string($value);
    }

    /**
     * Check if array
     */
    public static function isArray(mixed $value): bool
    {
        return is_array($value);
    }

    /**
     * Check if object
     */
    public static function isObject(mixed $value): bool
    {
        return is_object($value);
    }

    /**
     * Check if boolean
     */
    public static function isBool(mixed $value): bool
    {
        return is_bool($value);
    }

    /**
     * Check if callable
     */
    public static function isCallable(mixed $value): bool
    {
        return is_callable($value);
    }

    /**
     * Check if iterable
     */
    public static function isIterable(mixed $value): bool
    {
        return is_iterable($value);
    }
}
