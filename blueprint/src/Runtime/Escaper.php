<?php

declare(strict_types=1);

namespace Blueprint\Engine\Runtime;

/**
 * HTML Escaper
 * 
 * Handles HTML escaping for template output.
 * 
 * @package Blueprint\Engine\Runtime
 */
class Escaper
{
    /**
     * Escape value for HTML output
     * 
     * @param mixed $value Value to escape
     * @return string
     */
    public static function escape(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return self::escapeArray($value);
        }

        if (is_object($value)) {
            return self::escapeObject($value);
        }

        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Escape array (convert to JSON and escape)
     * 
     * @param array $value Array to escape
     * @return string
     */
    protected static function escapeArray(array $value): string
    {
        return htmlspecialchars(json_encode($value, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Escape object (convert to JSON and escape)
     * 
     * @param object $value Object to escape
     * @return string
     */
    protected static function escapeObject(object $value): string
    {
        if (method_exists($value, '__toString')) {
            return htmlspecialchars((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        
        return htmlspecialchars(json_encode($value, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Escape HTML attribute value
     * 
     * @param string $value Value to escape
     * @return string
     */
    public static function escapeAttribute(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Escape JavaScript string
     * 
     * @param string $value Value to escape
     * @return string
     */
    public static function escapeJs(string $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    /**
     * Escape CSS string
     * 
     * @param string $value Value to escape
     * @return string
     */
    public static function escapeCss(string $value): string
    {
        return preg_replace('/[^a-zA-Z0-9-_]/', '\\\\$0', $value);
    }

    /**
     * Escape URL
     * 
     * @param string $value Value to escape
     * @return string
     */
    public static function escapeUrl(string $value): string
    {
        return rawurlencode($value);
    }
}
