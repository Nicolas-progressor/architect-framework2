<?php

declare(strict_types=1);

namespace Architect\Services\Template\ElementLoader;

use Architect\Services\Template\Contracts\ElementLoaderInterface;
use Architect\Services\Template\Util\JsonParser;

/**
 * Loads template elements from JSON configuration files.
 */
final class ElementLoader implements ElementLoaderInterface
{
    private const ELEMENTS_FILE = 'elements.json';
    private const ELEMENTS_DIR = 'elements';

    public function load(string $templatePath): array
    {
        $file = rtrim($templatePath, '/') . '/' . self::ELEMENTS_FILE;
        return JsonParser::parseFile($file);
    }

    public function loadRouted(
        string $templatePath,
        string $module,
        string $controller,
        string $action
    ): array {
        $dir = rtrim($templatePath, '/') . '/' . self::ELEMENTS_DIR . '/';

        if (!is_dir($dir)) {
            return [];
        }

        $files = glob($dir . '*.json');

        if ($files === false) {
            return [];
        }

        $elements = [];

        foreach ($files as $file) {
            $data = JsonParser::parseFile($file);
            $routed = $this->extractRouted($data, $module, $controller, $action);
            $elements = [...$elements, ...$routed];
        }

        return $elements;
    }

    /**
     * Extract elements matching current route.
     */
    private function extractRouted(
        array $data,
        string $module,
        string $controller,
        string $action
    ): array {
        $elements = [];

        // Structure: module -> controller -> action
        if (isset($data[$module][$controller][$action])) {
            $elements = [...$elements, ...$this->normalizeElements($data[$module][$controller][$action])];
        }

        // Structure: module -> controller (for all actions)
        if (isset($data[$module][$controller]) && is_array($data[$module][$controller])) {
            // Skip if it's action-specific (already handled above)
            if (!isset($data[$module][$controller][$action])) {
                $elements = [...$elements, ...$this->normalizeElements($data[$module][$controller])];
            }
        }

        // Structure: action -> element (backward compatibility)
        if (isset($data[$action]) && is_array($data[$action])) {
            $elements = [...$elements, ...$this->normalizeElements($data[$action])];
        }

        return $elements;
    }

    /**
     * Normalize elements to consistent format.
     */
    private function normalizeElements(array $elements): array
    {
        $normalized = [];

        foreach ($elements as $key => $element) {
            if (is_array($element)) {
                $normalized[$key] = $element;
            }
        }

        return $normalized;
    }
}
