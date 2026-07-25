<?php

declare(strict_types=1);

namespace Blueprint\Engine\Functions;

/**
 * Debug Functions
 * 
 * Functions for debugging.
 * 
 * @package Blueprint\Engine\Functions
 */
class DebugFunctions
{
    /**
     * Get all debug functions
     * 
     * @return array<string, callable>
     */
    public static function getFunctions(): array
    {
        return [
            'dump' => [self::class, 'dump'],
            'dd' => [self::class, 'dd'],
            'var_export' => [self::class, 'varExport'],
            'print_r' => [self::class, 'printR'],
            'gettype' => [self::class, 'getType'],
            'get_class' => [self::class, 'getClass'],
            'debug_backtrace' => [self::class, 'debugBacktrace'],
            'memory_get_usage' => [self::class, 'memoryGetUsage'],
            'memory_get_peak_usage' => [self::class, 'memoryGetPeakUsage'],
        ];
    }

    /**
     * Dump variable
     */
    public static function dump(mixed ...$vars): string
    {
        ob_start();
        foreach ($vars as $var) {
            var_dump($var);
        }
        return ob_get_clean() ?: '';
    }

    /**
     * Dump and die
     */
    public static function dd(mixed ...$vars): void
    {
        foreach ($vars as $var) {
            var_dump($var);
        }
        exit(1);
    }

    /**
     * Variable export
     */
    public static function varExport(mixed $var, bool $return = true): string
    {
        return var_export($var, $return);
    }

    /**
     * Print readable
     */
    public static function printR(mixed $var, bool $return = true): string
    {
        return print_r($var, $return);
    }

    /**
     * Get type
     */
    public static function getType(mixed $var): string
    {
        return gettype($var);
    }

    /**
     * Get class name
     */
    public static function getClass(object $object): string
    {
        return get_class($object);
    }

    /**
     * Debug backtrace
     */
    public static function debugBacktrace(int $options = DEBUG_BACKTRACE_PROVIDE_OBJECT, int $limit = 0): array
    {
        return debug_backtrace($options, $limit);
    }

    /**
     * Memory usage
     */
    public static function memoryGetUsage(bool $real = false): int
    {
        return memory_get_usage($real);
    }

    /**
     * Peak memory usage
     */
    public static function memoryGetPeakUsage(bool $real = false): int
    {
        return memory_get_peak_usage($real);
    }
}
