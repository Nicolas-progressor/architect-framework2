<?php

declare(strict_types=1);

namespace Architect\Services\Template\Contracts;

/**
 * Interface for Template service.
 *
 * Composes smaller interfaces for better segregation (ISP).
 */
interface TemplateInterface extends
    TemplateStateInterface,
    TemplateContentInterface,
    TemplateElementsInterface
{
    /**
     * Set template by name.
     */
    public function setTemplate(string $name): void;

    /**
     * Set template from specified app.
     */
    public function setTemplateFromApp(string $appName, string $templateName): void;

    /**
     * Set template path directly.
     */
    public function setTemplatePath(string $path): void;

    /**
     * Get template path.
     */
    public function getTemplatePath(): ?string;

    /**
     * Get template name.
     */
    public function getTemplateName(): ?string;

    /**
     * Render template.
     */
    public function render(): void;
}
