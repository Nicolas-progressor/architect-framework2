<?php

declare(strict_types=1);

namespace Architect\Services\I18n\Contracts;

/**
 * Interface for language detection strategies.
 */
interface LanguageDetectorInterface
{
    /**
     * Detect the preferred language.
     *
     * @return string Language code (e.g., 'ru', 'en')
     */
    public function detect(): string;
}