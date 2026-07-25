<?php

declare(strict_types=1);

namespace Blueprint\Engine\Functions;

/**
 * URL Functions
 * 
 * Functions for URL handling.
 * Note: These functions are standalone and don't depend on Architect framework.
 * For framework integration, use the integration layer.
 * 
 * @package Blueprint\Engine\Functions
 */
class UrlFunctions
{
    /**
     * Get all URL functions
     * 
     * @return array<string, callable>
     */
    public static function getFunctions(): array
    {
        return [
            'url' => [self::class, 'url'],
            'asset' => [self::class, 'asset'],
            'route' => [self::class, 'route'],
            'href' => [self::class, 'href'],
            'urlencode' => [self::class, 'urlencode'],
            'urldecode' => [self::class, 'urldecode'],
            'rawurlencode' => [self::class, 'rawurlencode'],
            'rawurldecode' => [self::class, 'rawurldecode'],
            'parse_url' => [self::class, 'parseUrl'],
            'http_build_query' => [self::class, 'httpBuildQuery'],
            'base_url' => [self::class, 'baseUrl'],
        ];
    }

    /**
     * Build URL
     */
    public static function url(string $path = '', array $params = []): string
    {
        $url = '/' . ltrim($path, '/');
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        return $url;
    }

    /**
     * Asset URL
     */
    public static function asset(string $path): string
    {
        return '/assets/' . ltrim($path, '/');
    }

    /**
     * Route URL (placeholder - override in integration layer)
     */
    public static function route(string $name, array $params = []): string
    {
        // Placeholder implementation
        // Override this in the integration layer for framework support
        return '/' . ltrim($name, '/') . (!empty($params) ? '?' . http_build_query($params) : '');
    }

    /**
     * Simple href
     */
    public static function href(string $path): string
    {
        return $path;
    }

    /**
     * URL encode
     */
    public static function urlencode(string $string): string
    {
        return urlencode($string);
    }

    /**
     * URL decode
     */
    public static function urldecode(string $string): string
    {
        return urldecode($string);
    }

    /**
     * Raw URL encode
     */
    public static function rawurlencode(string $string): string
    {
        return rawurlencode($string);
    }

    /**
     * Raw URL decode
     */
    public static function rawurldecode(string $string): string
    {
        return rawurldecode($string);
    }

    /**
     * Parse URL
     */
    public static function parseUrl(string $url, int $component = -1): mixed
    {
        return parse_url($url, $component);
    }

    /**
     * Build HTTP query
     */
    public static function httpBuildQuery(array $data, string $prefix = '', string $separator = '&', int $encType = PHP_QUERY_RFC1738): string
    {
        return http_build_query($data, $prefix, $separator, $encType);
    }

    /**
     * Base URL
     */
    public static function baseUrl(): string
    {
        if (isset($_SERVER['HTTP_HOST'])) {
            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            return $protocol . '://' . $_SERVER['HTTP_HOST'];
        }
        return '';
    }
}
