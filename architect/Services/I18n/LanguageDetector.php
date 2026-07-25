<?php

declare(strict_types=1);

namespace Architect\Services\I18n;

use Architect\Services\I18n\Contracts\LanguageDetectorInterface;

/**
 * Chain-of-responsibility language detector.
 */
class LanguageDetector implements LanguageDetectorInterface
{
    /**
     * @var array<callable(): ?string>
     */
    private array $sources = [];

    /**
     * @var string Default language if no detection succeeds.
     */
    private string $defaultLanguage;

    /**
     * @param string $defaultLanguage
     */
    public function __construct(string $defaultLanguage = 'ru')
    {
        $this->defaultLanguage = $defaultLanguage;
        $this->addDefaultSources();
    }

    /**
     * Add a custom detection source.
     *
     * @param callable $source Callable that returns language code or null.
     * @return self
     */
    public function addSource(callable $source): self
    {
        $this->sources[] = $source;
        return $this;
    }

    /**
     * Detect language by iterating through sources.
     *
     * @return string
     */
    public function detect(): string
    {
        foreach ($this->sources as $source) {
            $lang = $source();
            if ($lang !== null && $lang !== '') {
                return $lang;
            }
        }

        return $this->defaultLanguage;
    }

    /**
     * Add default detection sources (query, cookie, session, Accept-Language header).
     */
    private function addDefaultSources(): void
    {
        // 1. Query parameter ?lang=...
        $this->addSource(function (): ?string {
            return $_GET['lang'] ?? null;
        });

        // 2. Cookie 'lang'
        $this->addSource(function (): ?string {
            return $_COOKIE['lang'] ?? null;
        });

        // 3. Session (if session is started)
        $this->addSource(function (): ?string {
            if (session_status() === PHP_SESSION_ACTIVE) {
                return $_SESSION['lang'] ?? null;
            }
            return null;
        });

        // 4. HTTP Accept-Language header
        $this->addSource(function (): ?string {
            $header = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null;
            if ($header) {
                $languages = explode(',', $header);
                $first = trim(explode(';', $languages[0])[0]);
                if (preg_match('/^[a-z]{2}(?:-[A-Z]{2})?$/', $first)) {
                    return strtolower(substr($first, 0, 2));
                }
            }
            return null;
        });
    }
}