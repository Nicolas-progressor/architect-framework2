<?php

declare(strict_types=1);

namespace Architect\Services\Blueprint\Elements;

/**
 * Loads element configuration from JSON files
 */
final class ElementConfigLoader
{
    /**
     * Load elements configuration from file
     */
    public function load(string $path): array
    {
        if (!file_exists($path)) {
            return [];
        }

        $content = file_get_contents($path);
        $data = json_decode($content, true);

        return $data ?? [];
    }

    /**
     * Load elements config for template
     */
    public function loadForTemplate(string $appDir, ?string $templateName): array
    {
        if (!$templateName) {
            return [];
        }

        $path = $appDir . 'template/' . $templateName . '/elements.json';
        return $this->load($path);
    }

    /**
     * Load routed elements configuration
     * Searches all JSON files in elements/ directory for route-based elements
     * Structure: module -> controller -> action -> elements
     */
    public function loadRoutedElements(string $appDir, ?string $templateName): array
    {
        if (!$templateName) {
            return [];
        }

        $elementsDir = $appDir . 'template/' . $templateName . '/elements/';

        if (!is_dir($elementsDir)) {
            return [];
        }

        $result = [];
        $files = glob($elementsDir . '*.json');

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $data = json_decode($content, true);

            if (!is_array($data)) {
                continue;
            }

            // Merge all route-based configurations
            $result = array_merge_recursive($result, $data);
        }

        return $result;
    }
}
