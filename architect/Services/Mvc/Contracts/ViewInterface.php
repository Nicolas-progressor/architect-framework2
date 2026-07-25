<?php

declare(strict_types=1);

namespace Architect\Services\Mvc\Contracts;

/**
 * Interface for View service.
 * 
 * Defines the contract for template rendering and data management.
 * 
 * @package Architect\Services\Mvc\Contracts
 */
interface ViewInterface
{
    /**
     * Set template directory.
     * 
     * @param string $dir Directory path
     */
    public function setTemplateDir(string $dir): void;

    /**
     * Get template directory.
     * 
     * @return string
     */
    public function getTemplateDir(): string;

    /**
     * Set module path.
     * 
     * Used for Blueprint context.
     * 
     * @param string $path Module path
     */
    public function setModulePath(string $path): void;

    /**
     * Set data for view.
     * 
     * Merges with existing data.
     * 
     * @param array $data View data
     */
    public function setData(array $data): void;

    /**
     * Render view template.
     * 
     * @param string $template Template name or path
     * @param array $data Template data
     * @param bool $setContent Whether to set content in template service
     * @return string Rendered content
     */
    public function render(string $template, array $data = [], bool $setContent = true): string;

    /**
     * Display view template.
     * 
     * Outputs rendered content directly.
     * 
     * @param string $template Template name or path
     * @param array $data Template data
     */
    public function display(string $template, array $data = []): void;

    /**
     * Clear view data.
     */
    public function clear(): void;
}
