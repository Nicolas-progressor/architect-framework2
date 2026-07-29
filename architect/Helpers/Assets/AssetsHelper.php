<?php

declare(strict_types=1);

namespace Architect\Helpers\Assets;

use Architect\Contracts\Core\ContainerInterface;
use Architect\Helpers\Core\AbstractHelper;

/**
 * Assets helper for managing CSS and JS files.
 */
class AssetsHelper extends AbstractHelper
{
    /** @var array CSS files */
    private array $css = [];

    /** @var array JS files */
    private array $js = [];

    /**
     * Create Assets helper with container.
     */
    public function __construct(ContainerInterface $container)
    {
        // Container available for future extensions
    }

    /**
     * Add CSS file.
     */
    public function css(string $file): self
    {
        if (!in_array($file, $this->css)) {
            $this->css[] = $file;
        }
        return $this;
    }

    /**
     * Add JS file.
     */
    public function js(string $file): self
    {
        if (!in_array($file, $this->js)) {
            $this->js[] = $file;
        }
        return $this;
    }

    /**
     * Get asset URL.
     */
    public function url(string $path): string
    {
        if (preg_match('#^https?://#', $path)) {
            return $path;
        }

        return (defined('ROOT_URL') ? ROOT_URL : '') . ltrim($path, '/');
    }

    /**
     * Generate img tag.
     */
    public function img(string $src, string $alt = ''): string
    {
        return '<img src="' . $this->url($src) . '" alt="' . htmlspecialchars($alt) . '">';
    }

    /**
     * Get all CSS files.
     */
    public function getCss(): array
    {
        return $this->css;
    }

    /**
     * Get all JS files.
     */
    public function getJs(): array
    {
        return $this->js;
    }

    /**
     * Clear all assets.
     */
    public function clear(): self
    {
        $this->css = [];
        $this->js = [];
        return $this;
    }

    /**
     * Render CSS tags.
     */
    public function renderCss(): string
    {
        $html = '';
        foreach ($this->css as $file) {
            $url = $this->url($file);
            $html .= '<link rel="stylesheet" href="' . $url . '">' . "\n";
        }
        return $html;
    }

    /**
     * Render JS tags.
     */
    public function renderJs(): string
    {
        $html = '';
        foreach ($this->js as $file) {
            $url = $this->url($file);
            $html .= '<script src="' . $url . '"></script>' . "\n";
        }
        return $html;
    }
}
