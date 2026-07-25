<?php

declare(strict_types=1);

namespace Blueprint\Engine\Filters;

/**
 * String Filters
 * 
 * Filters for string manipulation.
 * 
 * @package Blueprint\Engine\Filters
 */
class StringFilters
{
    /**
     * Get all string filters
     * 
     * @return array<string, callable>
     */
    public static function getFilters(): array
    {
        return [
            'upper' => [self::class, 'upper'],
            'lower' => [self::class, 'lower'],
            'capitalize' => [self::class, 'capitalize'],
            'title' => [self::class, 'title'],
            'trim' => [self::class, 'trim'],
            'striptags' => [self::class, 'striptags'],
            'nl2br' => [self::class, 'nl2br'],
            'truncate' => [self::class, 'truncate'],
            'wordwrap' => [self::class, 'wordwrap'],
            'replace' => [self::class, 'replace'],
            'pad' => [self::class, 'pad'],
            'slice' => [self::class, 'slice'],
            'split' => [self::class, 'split'],
            'strip' => [self::class, 'trim'],
            'stripslashes' => [self::class, 'stripslashes'],
            'escape' => [self::class, 'escape'],
            'e' => [self::class, 'escape'],
        ];
    }

    /**
     * Convert to uppercase
     */
    public static function upper(mixed $value): string
    {
        if (is_array($value)) {
            return implode(' ', array_map(self::upper(...), $value));
        }
        return mb_strtoupper((string) $value, 'UTF-8');
    }

    /**
     * Convert to lowercase
     */
    public static function lower(mixed $value): string
    {
        return mb_strtolower((string) $value, 'UTF-8');
    }

    /**
     * Capitalize first letter
     */
    public static function capitalize(mixed $value): string
    {
        return mb_convert_case((string) $value, MB_CASE_TITLE_SIMPLE, 'UTF-8');
    }

    /**
     * Title case
     */
    public static function title(mixed $value): string
    {
        return mb_convert_case((string) $value, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Trim whitespace
     */
    public static function trim(mixed $value): string
    {
        return trim((string) $value);
    }

    /**
     * Strip HTML tags
     */
    public static function striptags(mixed $value): string
    {
        return strip_tags((string) $value);
    }

    /**
     * Convert newlines to <br>
     */
    public static function nl2br(mixed $value): string
    {
        return nl2br((string) $value);
    }

    /**
     * Truncate string
     * 
     * Truncates a string to a given length, including suffix.
     * Example: truncate('Hello World', 5) returns 'He...' (2 chars + 3 suffix = 5 total)
     */
    public static function truncate(mixed $value, mixed $length = 100, mixed $suffix = '...'): string
    {
        $str = (string) $value;
        $len = (int) $length;
        $suffixStr = (string) $suffix;
        $suffixLen = mb_strlen($suffixStr, 'UTF-8');
        
        if (mb_strlen($str, 'UTF-8') <= $len) {
            return $str;
        }

        // Calculate available space for content (total length - suffix length)
        $contentLen = max(0, $len - $suffixLen);
        
        return mb_substr($str, 0, $contentLen, 'UTF-8') . $suffixStr;
    }

    /**
     * Word wrap
     */
    public static function wordwrap(mixed $value, mixed $width = 75, mixed $break = "\n", mixed $cut = false): string
    {
        return wordwrap((string) $value, (int) $width, (string) $break, (bool) $cut);
    }

    /**
     * Replace string
     */
    public static function replace(mixed $value, mixed $search, mixed $replace = ''): string
    {
        if (is_array($value)) {
            return implode('', array_map(fn($item) => self::replace($item, $search, $replace), $value));
        }
        
        return str_replace((string) $search, (string) $replace, (string) $value);
    }

    /**
     * Pad string
     */
    public static function pad(mixed $value, mixed $length, mixed $padString = ' ', mixed $padType = STR_PAD_RIGHT): string
    {
        return str_pad((string) $value, (int) $length, (string) $padString, (int) $padType);
    }

    /**
     * Slice string
     */
    public static function slice(mixed $value, mixed $offset, mixed $length = null): string
    {
        return mb_substr((string) $value, (int) $offset, $length !== null ? (int) $length : null, 'UTF-8');
    }

    /**
     * Split string
     */
    public static function split(mixed $value, mixed $delimiter = ' '): array
    {
        return explode((string) $delimiter, (string) $value);
    }

    /**
     * Strip slashes
     */
    public static function stripslashes(mixed $value): string
    {
        return stripslashes((string) $value);
    }

    /**
     * HTML escape
     */
    public static function escape(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
