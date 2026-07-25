<?php

declare(strict_types=1);

namespace Architect\Services\I18n\Contracts;

/**
 * Interface for Language service.
 */
interface LanguageInterface
{
    /**
     * Get translation by key.
     *
     * @param string $key Dot‑notation key (section.key) or just section if module provided
     * @param string|null $module If provided, loads translations from that module first
     * @param mixed $default Default value if translation not found
     * @return mixed
     */
    public function get(string $key, ?string $module = null, mixed $default = null): mixed;

    /**
     * Get all translations for a section or all sections.
     *
     * @param string|null $section If null, returns all loaded translations
     * @return array<string, mixed>
     */
    public function getAll(?string $section = null): array;

    /**
     * Set current language.
     */
    public function setLanguage(string $lang): void;

    /**
     * Get current language.
     */
    public function getLanguage(): string;
}
