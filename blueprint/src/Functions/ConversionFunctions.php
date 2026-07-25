<?php

declare(strict_types=1);

namespace Blueprint\Engine\Functions;

/**
 * Conversion Functions
 * 
 * Functions for type conversion.
 * 
 * @package Blueprint\Engine\Functions
 */
class ConversionFunctions
{
    /**
     * Get all conversion functions
     * 
     * @return array<string, callable>
     */
    public static function getFunctions(): array
    {
        return [
            'json_encode' => [self::class, 'jsonEncode'],
            'json_decode' => [self::class, 'jsonDecode'],
            'intval' => [self::class, 'intVal'],
            'floatval' => [self::class, 'floatVal'],
            'strval' => [self::class, 'strVal'],
            'boolval' => [self::class, 'boolVal'],
            'serialize' => [self::class, 'serialize'],
            'unserialize' => [self::class, 'unserialize'],
            'base64_encode' => [self::class, 'base64Encode'],
            'base64_decode' => [self::class, 'base64Decode'],
            'htmlentities' => [self::class, 'htmlentities'],
            'html_entity_decode' => [self::class, 'htmlEntityDecode'],
            'htmlspecialchars' => [self::class, 'htmlspecialchars'],
            'htmlspecialchars_decode' => [self::class, 'htmlspecialcharsDecode'],
        ];
    }

    /**
     * JSON encode
     */
    public static function jsonEncode(mixed $value, int $options = 0): string
    {
        return json_encode($value, $options | JSON_UNESCAPED_UNICODE) ?: '';
    }

    /**
     * JSON decode
     */
    public static function jsonDecode(string $json, bool $assoc = true, int $depth = 512, int $options = 0): mixed
    {
        return json_decode($json, $assoc, $depth, $options);
    }

    /**
     * Integer value
     */
    public static function intVal(mixed $var, int $base = 10): int
    {
        return intval($var, $base);
    }

    /**
     * Float value
     */
    public static function floatVal(mixed $var): float
    {
        return floatval($var);
    }

    /**
     * String value
     */
    public static function strVal(mixed $var): string
    {
        return strval($var);
    }

    /**
     * Boolean value
     */
    public static function boolVal(mixed $var): bool
    {
        return boolval($var);
    }

    /**
     * Serialize
     */
    public static function serialize(mixed $value): string
    {
        return serialize($value);
    }

    /**
     * Unserialize
     */
    public static function unserialize(string $data): mixed
    {
        return unserialize($data);
    }

    /**
     * Base64 encode
     */
    public static function base64Encode(string $data): string
    {
        return base64_encode($data);
    }

    /**
     * Base64 decode
     */
    public static function base64Decode(string $data): string|false
    {
        return base64_decode($data);
    }

    /**
     * HTML entities
     */
    public static function htmlentities(string $string, int $flags = ENT_QUOTES | ENT_HTML5, ?string $encoding = null): string
    {
        return htmlentities($string, $flags, $encoding ?? 'UTF-8');
    }

    /**
     * HTML entity decode
     */
    public static function htmlEntityDecode(string $string, int $flags = ENT_QUOTES | ENT_HTML5, ?string $encoding = null): string
    {
        return html_entity_decode($string, $flags, $encoding ?? 'UTF-8');
    }

    /**
     * HTML special chars
     */
    public static function htmlspecialchars(string $string, int $flags = ENT_QUOTES | ENT_HTML5, ?string $encoding = null): string
    {
        return htmlspecialchars($string, $flags, $encoding ?? 'UTF-8');
    }

    /**
     * HTML special chars decode
     */
    public static function htmlspecialcharsDecode(string $string, int $flags = ENT_QUOTES | ENT_HTML5): string
    {
        return htmlspecialchars_decode($string, $flags);
    }
}
