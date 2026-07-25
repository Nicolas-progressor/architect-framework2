<?php

declare(strict_types=1);

namespace Blueprint\Engine;

/**
 * Base Element
 * 
 * Abstract base class for creating template elements.
 * Supports two rendering modes:
 * 
 * 1. Pure PHP rendering:
 *    - Override render() to return HTML directly
 *    - hasTemplate() returns false (default)
 * 
 * 2. Template rendering:
 *    - Set $template property to .blu path (e.g., 'elements/alert')
 *    - Override getTemplateData() to prepare data
 *    - hasTemplate() returns true automatically
 *    - render() returns empty string (ElementManager will call template)
 * 
 * @package Blueprint\Engine
 */
abstract class BaseElement implements ElementInterface
{
    /**
     * Element name
     * 
     * @var string
     */
    protected string $name;
    
    /**
     * Template path (relative to template paths, without extension)
     * Example: 'elements/alert' for elements/alert.blu
     * 
     * @var string|null
     */
    protected ?string $template = null;
    
    /**
     * Blueprint instance (available during rendering)
     * 
     * @var Blueprint|null
     */
    protected ?Blueprint $blueprint = null;
    
    /**
     * Input data (available during rendering)
     * 
     * @var array
     */
    protected array $data = [];
    
    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->name;
    }
    
    /**
     * @inheritDoc
     */
    public function hasTemplate(): bool
    {
        return $this->template !== null;
    }
    
    /**
     * @inheritDoc
     */
    public function getTemplate(): ?string
    {
        return $this->template;
    }
    
    /**
     * @inheritDoc
     */
    public function getTemplateData(array $data, Blueprint $blueprint): array
    {
        // Default: pass all data as-is
        // Override in subclass to transform/prepare data
        return $data;
    }
    
    /**
     * @inheritDoc
     */
    public function render(array $data, Blueprint $blueprint): string
    {
        // Store for use in helper methods
        $this->blueprint = $blueprint;
        $this->data = $data;
        
        // If using template, return empty string
        // ElementManager will render the template
        if ($this->hasTemplate()) {
            return '';
        }
        
        // Pure PHP rendering - override in subclass
        return '';
    }
    
    /**
     * Get input parameter
     * 
     * @param string $key Parameter key
     * @param mixed $default Default value
     * @return mixed
     */
    protected function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }
    
    /**
     * Check if parameter exists
     * 
     * @param string $key Parameter key
     * @return bool
     */
    protected function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }
    
    /**
     * Get all input data
     * 
     * @return array
     */
    protected function all(): array
    {
        return $this->data;
    }
    
    /**
     * Escape HTML
     * 
     * @param string $value Value to escape
     * @return string
     */
    protected function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Generate HTML attributes string
     * 
     * @param array $attributes Attributes array
     * @return string
     */
    protected function attributes(array $attributes): string
    {
        $result = [];
        
        foreach ($attributes as $key => $value) {
            if ($value === true) {
                $result[] = $this->escape($key);
            } elseif ($value !== false && $value !== null) {
                $result[] = $this->escape($key) . '="' . $this->escape((string) $value) . '"';
            }
        }
        
        return $result ? ' ' . implode(' ', $result) : '';
    }
    
    /**
     * Build CSS class string from array
     * 
     * @param array $classes Array of class names (can be associative for conditional)
     * @return string
     */
    protected function classList(array $classes): string
    {
        $result = [];
        
        foreach ($classes as $key => $value) {
            if (is_int($key)) {
                // Indexed array - always include
                if ($value) {
                    $result[] = $value;
                }
            } else {
                // Associative array - include if value is true
                if ($value) {
                    $result[] = $key;
                }
            }
        }
        
        return implode(' ', $result);
    }
}
