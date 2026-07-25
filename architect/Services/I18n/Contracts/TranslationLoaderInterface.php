<?php

declare(strict_types=1);

namespace Architect\Services\I18n\Contracts;

/**
 * Interface for loading translations from a source.
 */
interface TranslationLoaderInterface
{
    /**
     * Load translations for a given section, module, and language.
     *
     * @param string $section Translation section (e.g., 'messages')
     * @param string $module Module name (e.g., 'sample')
     * @param string $language Language code (e.g., 'ru')
     * @return array<string, mixed> Translation key-value pairs
     */
    public function load(string $section, string $module, string $language): array;
}