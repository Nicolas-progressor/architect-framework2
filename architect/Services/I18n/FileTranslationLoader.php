<?php

declare(strict_types=1);

namespace Architect\Services\I18n;

use Architect\Services\I18n\Contracts\TranslationLoaderInterface;

/**
 * Load translations from JSON files in module directories.
 */
class FileTranslationLoader implements TranslationLoaderInterface
{
    /**
     * Base path to application directory.
     *
     * @var string
     */
    private string $basePath;

    /**
     * @param string $basePath
     */
    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, DIRECTORY_SEPARATOR);
    }

    /**
     * {@inheritdoc}
     */
    public function load(string $section, string $module, string $language): array
    {
        $path = $this->buildPath($section, $module, $language);

        if (!file_exists($path)) {
            // Fallback to default language (ru) if requested language file doesn't exist
            if ($language !== 'ru') {
                $path = $this->buildPath($section, $module, 'ru');
            }
            if (!file_exists($path)) {
                return [];
            }
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return [];
        }

        $data = json_decode($content, true);
        if (!is_array($data)) {
            return [];
        }

        return $data[$language] ?? $data['ru'] ?? $data;
    }

    /**
     * Build file path for translation JSON.
     *
     * @param string $section
     * @param string $module
     * @param string $language
     * @return string
     */
    private function buildPath(string $section, string $module, string $language): string
    {
        return sprintf(
            '%s/modules/%s/lang/%s/%s.json',
            $this->basePath,
            $module,
            $section,
            $section
        );
    }
}
