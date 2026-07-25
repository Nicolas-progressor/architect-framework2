<?php

declare(strict_types=1);

namespace Blueprint\Engine\Functions;

/**
 * String Functions
 * 
 * Functions for string manipulation.
 * 
 * @package Blueprint\Engine\Functions
 */
class StringFunctions
{
    /**
     * Get all string functions
     * 
     * @return array<string, callable>
     */
    public static function getFunctions(): array
    {
        return [
            'str_replace' => [self::class, 'strReplace'],
            'str_contains' => [self::class, 'strContains'],
            'str_starts_with' => [self::class, 'strStartsWith'],
            'str_ends_with' => [self::class, 'strEndsWith'],
            'str_repeat' => [self::class, 'strRepeat'],
            'str_pad' => [self::class, 'strPad'],
            'str_split' => [self::class, 'strSplit'],
            'strlen' => [self::class, 'strlen'],
            'substr' => [self::class, 'substr'],
            'strpos' => [self::class, 'strpos'],
            'strtolower' => [self::class, 'strtolower'],
            'strtoupper' => [self::class, 'strtoupper'],
            'ucfirst' => [self::class, 'ucfirst'],
            'lcfirst' => [self::class, 'lcfirst'],
            'ucwords' => [self::class, 'ucwords'],
            'trim' => [self::class, 'trim'],
            'ltrim' => [self::class, 'ltrim'],
            'rtrim' => [self::class, 'rtrim'],
            'explode' => [self::class, 'explode'],
            'implode' => [self::class, 'implode'],
            'sprintf' => [self::class, 'sprintf'],
            'printf' => [self::class, 'printf'],
            'wordwrap' => [self::class, 'wordwrap'],
            'chunk_split' => [self::class, 'chunkSplit'],
        ];
    }

    /**
     * String replace
     */
    public static function strReplace(string $search, string $replace, string $subject): string
    {
        return str_replace($search, $replace, $subject);
    }

    /**
     * String contains
     */
    public static function strContains(string $haystack, string $needle): bool
    {
        return str_contains($haystack, $needle);
    }

    /**
     * String starts with
     */
    public static function strStartsWith(string $haystack, string $needle): bool
    {
        return str_starts_with($haystack, $needle);
    }

    /**
     * String ends with
     */
    public static function strEndsWith(string $haystack, string $needle): bool
    {
        return str_ends_with($haystack, $needle);
    }

    /**
     * String repeat
     */
    public static function strRepeat(string $string, int $times): string
    {
        return str_repeat($string, $times);
    }

    /**
     * String pad
     */
    public static function strPad(string $input, int $padLength, string $padString = ' ', int $padType = STR_PAD_RIGHT): string
    {
        return str_pad($input, $padLength, $padString, $padType);
    }

    /**
     * String split
     */
    public static function strSplit(string $string, int $length = 1): array
    {
        return str_split($string, $length);
    }

    /**
     * String length
     */
    public static function strlen(string $string): int
    {
        return mb_strlen($string, 'UTF-8');
    }

    /**
     * Substring
     */
    public static function substr(string $string, int $offset, ?int $length = null): string
    {
        return mb_substr($string, $offset, $length, 'UTF-8');
    }

    /**
     * String position
     */
    public static function strpos(string $haystack, string $needle, int $offset = 0): int|false
    {
        return mb_strpos($haystack, $needle, $offset, 'UTF-8');
    }

    /**
     * Lowercase
     */
    public static function strtolower(string $string): string
    {
        return mb_strtolower($string, 'UTF-8');
    }

    /**
     * Uppercase
     */
    public static function strtoupper(string $string): string
    {
        return mb_strtoupper($string, 'UTF-8');
    }

    /**
     * Uppercase first
     */
    public static function ucfirst(string $string): string
    {
        return mb_convert_case($string, MB_CASE_TITLE_SIMPLE, 'UTF-8');
    }

    /**
     * Lowercase first
     */
    public static function lcfirst(string $string): string
    {
        return mb_strtolower(mb_substr($string, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($string, 1, null, 'UTF-8');
    }

    /**
     * Uppercase words
     */
    public static function ucwords(string $string): string
    {
        return mb_convert_case($string, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Trim
     */
    public static function trim(string $string, string $chars = " \t\n\r\0\x0B"): string
    {
        return trim($string, $chars);
    }

    /**
     * Left trim
     */
    public static function ltrim(string $string, string $chars = " \t\n\r\0\x0B"): string
    {
        return ltrim($string, $chars);
    }

    /**
     * Right trim
     */
    public static function rtrim(string $string, string $chars = " \t\n\r\0\x0B"): string
    {
        return rtrim($string, $chars);
    }

    /**
     * Explode
     */
    public static function explode(string $separator, string $string, int $limit = PHP_INT_MAX): array
    {
        return explode($separator, $string, $limit);
    }

    /**
     * Implode
     */
    public static function implode(string $separator, array $array): string
    {
        return implode($separator, $array);
    }

    /**
     * Sprintf
     */
    public static function sprintf(string $format, mixed ...$args): string
    {
        return sprintf($format, ...$args);
    }

    /**
     * Printf
     */
    public static function printf(string $format, mixed ...$args): int
    {
        return printf($format, ...$args);
    }

    /**
     * Wordwrap
     */
    public static function wordwrap(string $string, int $width = 75, string $break = "\n", bool $cut = false): string
    {
        return wordwrap($string, $width, $break, $cut);
    }

    /**
     * Chunk split
     */
    public static function chunkSplit(string $string, int $length = 76, string $ending = "\r\n"): string
    {
        return chunk_split($string, $length, $ending);
    }
}
