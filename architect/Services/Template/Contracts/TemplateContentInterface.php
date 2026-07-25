<?php

declare(strict_types=1);

namespace Architect\Services\Template\Contracts;

/**
 * Interface for template content management.
 */
interface TemplateContentInterface
{
    /**
     * Set content for template.
     */
    public function setContent(string $content): void;

    /**
     * Get content.
     */
    public function getContent(): ?string;

    /**
     * Set page title.
     */
    public function setTitle(string $title): void;

    /**
     * Get page title.
     */
    public function getTitle(): string;
}
