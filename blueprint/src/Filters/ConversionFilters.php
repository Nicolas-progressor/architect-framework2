<?php

declare(strict_types=1);

namespace Blueprint\Engine\Filters;

/**
 * Conversion Filters
 * 
 * Filters for type conversion and data transformation.
 * 
 * @package Blueprint\Engine\Filters
 */
class ConversionFilters
{
    /**
     * Get all conversion filters
     * 
     * @return array<string, callable>
     */
    public static function getFilters(): array
    {
        return [
            'json_encode' => [self::class, 'jsonEncode'],
            'json' => [self::class, 'jsonEncode'],
            'json_decode' => [self::class, 'jsonDecode'],
            'serialize' => [self::class, 'serialize'],
            'unserialize' => [self::class, 'unserialize'],
            'base64_encode' => [self::class, 'base64Encode'],
            'base64_decode' => [self::class, 'base64Decode'],
            'url_encode' => [self::class, 'urlEncode'],
            'url_decode' => [self::class, 'urlDecode'],
            'raw' => [self::class, 'raw'],
            'string' => [self::class, 'toString'],
        ];
    }

    /**
     * JSON encode
     */
    public static function jsonEncode(mixed $value, mixed $options = 0): string
    {
        return json_encode($value, (int) $options | JSON_UNESCAPED_UNICODE) ?: '';
    }

    /**
     * JSON decode
     */
    public static function jsonDecode(mixed $value, mixed $assoc = true): mixed
    {
        return json_decode((string) $value, (bool) $assoc);
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
    public static function unserialize(mixed $value): mixed
    {
        return unserialize((string) $value);
    }

    /**
     * Base64 encode
     */
    public static function base64Encode(mixed $value): string
    {
        return base64_encode((string) $value);
    }

    /**
     * Base64 decode
     */
    public static function base64Decode(mixed $value): string|false
    {
        return base64_decode((string) $value);
    }

    /**
     * URL encode
     */
    public static function urlEncode(mixed $value): string
    {
        return urlencode((string) $value);
    }

    /**
     * URL decode
     */
    public static function urlDecode(mixed $value): string
    {
        return urldecode((string) $value);
    }

    /**
     * Raw output (no escaping)
     */
    public static function raw(mixed $value): string
    {
        return (string) $value;
    }

    /**
     * Convert to string
     */
    public static function toString(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE) ?: '';
        }
        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                return (string) $value;
            }
            return json_encode($value, JSON_UNESCAPED_UNICODE) ?: '';
        }
        return (string) $value;
    }
}
