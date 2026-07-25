<?php

declare(strict_types=1);

namespace Architect\Services\Template\Util;

/**
 * Utility for parsing JSON files with consistent error handling.
 */
final class JsonParser
{
    /**
     * Parse JSON file and return array.
     */
    public static function parseFile(string $path): array
    {
        if (!file_exists($path)) {
            return [];
        }

        $content = file_get_contents($path);

        if ($content === false) {
            return [];
        }

        return self::parseString($content);
    }

    /**
     * Parse JSON string and return array.
     */
    public static function parseString(string $content): array
    {
        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            return is_array($data) ? $data : [];
        } catch (\JsonException) {
            return [];
        }
    }
}
