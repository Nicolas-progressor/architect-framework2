<?php

declare(strict_types=1);

namespace Architect\Services\I18n;

/**
 * Configuration for Language service.
 */
class LanguageConfig
{
    /**
     * @param string $defaultLanguage Default language code (e.g., 'ru')
     * @param array<string> $supportedLanguages List of supported language codes
     * @param string $basePath Base application directory path
     * @param array<string> $detectionSources Ordered list of detection source names
     */
    public function __construct(
        private string $defaultLanguage = 'ru',
        private array $supportedLanguages = ['ru', 'en'],
        private string $basePath = '',
        private array $detectionSources = ['query', 'cookie', 'session', 'header']
    ) {}

    /**
     * Get default language.
     */
    public function getDefaultLanguage(): string
    {
        return $this->defaultLanguage;
    }

    /**
     * Get supported languages.
     *
     * @return array<string>
     */
    public function getSupportedLanguages(): array
    {
        return $this->supportedLanguages;
    }

    /**
     * Check if language is supported.
     */
    public function isSupported(string $language): bool
    {
        return in_array($language, $this->supportedLanguages, true);
    }

    /**
     * Get base path.
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Get detection sources order.
     *
     * @return array<string>
     */
    public function getDetectionSources(): array
    {
        return $this->detectionSources;
    }

    /**
     * Create default configuration using the current app directory.
     *
     * @param string $basePath
     * @return self
     */
    public static function default(string $basePath = ''): self
    {
        if ($basePath === '') {
            $basePath = dirname(__DIR__, 4) . '/app';
        }

        return new self(
            defaultLanguage: 'ru',
            supportedLanguages: ['ru', 'en'],
            basePath: $basePath,
            detectionSources: ['query', 'cookie', 'session', 'header']
        );
    }
}