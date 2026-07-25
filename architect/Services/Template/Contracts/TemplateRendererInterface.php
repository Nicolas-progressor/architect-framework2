<?php

declare(strict_types=1);

namespace Architect\Services\Template\Contracts;

/**
 * Interface for template rendering strategy.
 */
interface TemplateRendererInterface
{
    /**
     * Check if this renderer can handle the template.
     */
    public function supports(string $templatePath): bool;

    /**
     * Render template with data.
     */
    public function render(string $templatePath, array $data): string;
}
