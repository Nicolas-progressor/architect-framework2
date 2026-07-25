<?php

declare(strict_types=1);

namespace Architect\Helpers\Title;

use Architect\Core\Contracts\ContainerInterface;
use Architect\Helpers\Core\AbstractHelper;

/**
 * Title helper for managing page titles.
 */
class TitleHelper extends AbstractHelper
{
    private string $title = '';
    private ?string $prefix = null;
    private ?string $suffix = null;
    private string $separator = ' | ';

    /**
     * Create Title helper with container.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->loadConfig($container);
    }
    
    /**
     * Load configuration from container.
     */
    private function loadConfig(ContainerInterface $container): void
    {
        try {
            if (!$container->has('config')) {
                return;
            }

            $config = $container->get('config');
            $this->title = $config->get('title', '');
            $this->prefix = $config->get('title_prefix');
            $this->suffix = $config->get('title_suffix');
            $this->separator = $config->get('title_separator', ' | ');
        } catch (\Throwable $e) {
            // Use defaults
        }
    }
    
    /**
     * Set page title.
     */
    public function set(string $title): self
    {
        $this->title = $title;
        return $this;
    }
    
    /**
     * Append to title.
     */
    public function append(string $title): self
    {
        $this->title .= $title;
        return $this;
    }
    
    /**
     * Prepend to title.
     */
    public function prepend(string $title): self
    {
        $this->title = $title . $this->title;
        return $this;
    }
    
    /**
     * Render full title with prefix/suffix.
     */
    public function render(): string
    {
        $parts = [];
        
        if ($this->prefix) {
            $parts[] = $this->prefix;
        }
        
        $parts[] = $this->title;
        
        if ($this->suffix) {
            $parts[] = $this->suffix;
        }
        
        return implode($this->separator, $parts);
    }
    
    /**
     * Get base title.
     */
    public function get(): string
    {
        return $this->title;
    }

    /**
     * Clear title.
     */
    public function clear(): self
    {
        $this->title = '';
        return $this;
    }
}