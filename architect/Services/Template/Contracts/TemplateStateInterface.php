<?php

declare(strict_types=1);

namespace Architect\Services\Template\Contracts;

/**
 * Interface for template state management.
 */
interface TemplateStateInterface
{
    /**
     * Check if template is enabled.
     */
    public function isEnabled(): bool;

    /**
     * Check if template is locked.
     */
    public function isLocked(): bool;

    /**
     * Disable template rendering.
     */
    public function disable(): void;

    /**
     * Enable template rendering.
     */
    public function enable(): void;

    /**
     * Lock template changes.
     */
    public function lock(): void;
}
