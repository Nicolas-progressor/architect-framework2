<?php

declare(strict_types=1);

namespace Blueprint\Engine;

/**
 * Element Interface
 * 
 * Interface for creating reusable template elements.
 * Elements are self-contained components with their own logic and rendering.
 * 
 * Two rendering modes:
 * 1. Pure PHP rendering - render() returns HTML directly
 * 2. Template rendering - hasTemplate() returns true, getTemplate() returns .blu path
 * 
 * @package Blueprint\Engine
 */
interface ElementInterface
{
    /**
     * Get element name
     * 
     * @return string
     */
    public function getName(): string;
    
    /**
     * Render element
     * 
     * If hasTemplate() returns true, this method should prepare data
     * and the ElementManager will call renderTemplate() automatically.
     * Otherwise, this method should return the complete HTML.
     * 
     * @param array $data Element data
     * @param Blueprint $blueprint Blueprint instance
     * @return string HTML output or empty string if using template
     */
    public function render(array $data, Blueprint $blueprint): string;
    
    /**
     * Check if element uses template file
     * 
     * @return bool True if element should render via .blu template
     */
    public function hasTemplate(): bool;
    
    /**
     * Get template path (relative to template paths)
     * 
     * Example: 'elements/alert' will look for elements/alert.blu
     * 
     * @return string|null Template path without extension
     */
    public function getTemplate(): ?string;
    
    /**
     * Get data for template rendering
     * 
     * Called when hasTemplate() returns true.
     * Should return prepared data for the template.
     * 
     * @param array $data Input data
     * @param Blueprint $blueprint Blueprint instance
     * @return array Data for template
     */
    public function getTemplateData(array $data, Blueprint $blueprint): array;
}
