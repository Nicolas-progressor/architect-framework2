<?php

declare(strict_types=1);

namespace Architect\Services\I18n;

use Architect\Support\AbstractService;
use Architect\Services\I18n\Contracts\LanguageInterface;
use Architect\Services\I18n\Contracts\LanguageDetectorInterface;
use Architect\Services\I18n\Contracts\TranslationLoaderInterface;

/**
 * Language service for internationalization.
 *
 * Uses detector for automatic language detection and loader for translation loading.
 */
class Language extends AbstractService implements LanguageInterface
{
    /**
     * @var string Current language
     */
    private string $lang;

    /**
     * @var array<string, array<string, mixed>> Translations cache
     */
    private array $translations = [];

    /**
     * @var LanguageDetectorInterface
     */
    private LanguageDetectorInterface $detector;

    /**
     * @var TranslationLoaderInterface
     */
    private TranslationLoaderInterface $loader;

    /**
     * @var LanguageConfig
     */
    private LanguageConfig $config;

    /**
     * Whether language has been detected automatically.
     *
     * @var bool
     */
    private bool $detected = false;

    /**
     * Create language service.
     *
     * @param \Architect\Core\Contracts\ContainerInterface $container
     * @param LanguageDetectorInterface|null $detector
     * @param TranslationLoaderInterface|null $loader
     * @param LanguageConfig|null $config
     */
    public function __construct(
        \Architect\Core\Contracts\ContainerInterface $container,
        ?LanguageDetectorInterface $detector = null,
        ?TranslationLoaderInterface $loader = null,
        ?LanguageConfig $config = null
    ) {
        parent::__construct($container);

        $this->config = $config ?? LanguageConfig::default();
        $this->detector = $detector ?? new LanguageDetector($this->config->getDefaultLanguage());
        $this->loader = $loader ?? new FileTranslationLoader($this->config->getBasePath());

        // Set default language, but do not auto‑detect yet (lazy detection)
        $this->lang = $this->config->getDefaultLanguage();
    }

    /**
     * Boot the service.
     *
     * Detects language automatically if not already set.
     */
    public function boot(): void
    {
        $this->detectLanguage();
    }

    /**
     * Detect language using detector.
     */
    private function detectLanguage(): void
    {
        if ($this->detected) {
            return;
        }

        $detected = $this->detector->detect();
        if ($this->config->isSupported($detected)) {
            $this->lang = $detected;
        }

        $this->detected = true;

        // Define global constant for backward compatibility
        if (!defined('ARC_LANG')) {
            define('ARC_LANG', $this->lang);
        }
    }

    /**
     * Set current language.
     *
     * Clears translation cache for the previous language.
     */
    public function setLanguage(string $lang): void
    {
        if ($lang !== $this->lang) {
            $this->translations = [];
        }
        $this->lang = $lang;
        $this->detected = true; // manual setting overrides detection
    }

    /**
     * Get current language.
     */
    public function getLanguage(): string
    {
        if (!$this->detected) {
            $this->detectLanguage();
        }
        return $this->lang;
    }


    /**
     * Load translation file for a section and module.
     *
     * @param string $section
     * @param string $module
     */
    public function file(string $section, string $module): void
    {
        $lang = $this->getLanguage();
        $translations = $this->loader->load($section, $module, $lang);

        if (!empty($translations)) {
            $this->translations[$section] = $translations;
        }
    }

    /**
     * Get translation by key.
     *
     * @param string $key Dot‑notation key (section.key) or just section if module provided
     * @param string|null $module If provided, loads translations from that module first
     * @param mixed $default Default value if translation not found
     * @return mixed
     */
    public function get(string $key, ?string $module = null, mixed $default = null): mixed
    {
        if ($module !== null) {
            $this->file($key, $module);
        }

        $parts = explode('.', $key, 2);
        $section = $parts[0];
        $transKey = $parts[1] ?? $key;

        return $this->translations[$section][$transKey] ?? $default;
    }

    /**
     * Get translation (alias).
     *
     * @param string $section
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function trans(string $section, string $key, mixed $default = null): mixed
    {
        return $this->translations[$section][$key] ?? $default;
    }

    /**
     * Get all translations for a section or all sections.
     *
     * @param string|null $section If null, returns all loaded translations
     * @return array
     */
    public function getAll(?string $section = null): array
    {
        if ($section === null) {
            return $this->translations;
        }

        return $this->translations[$section] ?? [];
    }

    /**
     * Check if translation exists.
     *
     * @param string $section
     * @param string $key
     * @return bool
     */
    public function has(string $section, string $key): bool
    {
        return isset($this->translations[$section][$key]);
    }

    /**
     * Get the underlying detector (for testing or customization).
     */
    public function getDetector(): LanguageDetectorInterface
    {
        return $this->detector;
    }

    /**
     * Get the underlying loader.
     */
    public function getLoader(): TranslationLoaderInterface
    {
        return $this->loader;
    }

    /**
     * Get the configuration.
     */
    public function getConfig(): LanguageConfig
    {
        return $this->config;
    }
}
