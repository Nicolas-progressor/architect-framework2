<?php

declare(strict_types=1);

namespace Architect\Helpers\Html;

use Architect\Contracts\Core\ContainerInterface;
use Architect\Helpers\Core\AbstractHelper;

/**
 * Html helper for generating HTML tags.
 */
class HtmlHelper extends AbstractHelper
{
    private ContainerInterface $container;

    /**
     * Create Html helper with container.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Generate URL for path.
     */
    public function href(string $path): string
    {
        // External URL
        if (str_starts_with($path, '//') || preg_match('#^https?://#', $path)) {
            return $path;
        }

        // Absolute path
        if (str_starts_with($path, '/')) {
            return $path;
        }

        // Get current app from apps service
        $app = '';
        try {
            if ($this->container->has('apps')) {
                $apps = $this->container->get('apps');
                $app = $apps->getCurrentApp();
            }
        } catch (\Throwable $e) {
            // Ignore
        }

        return $app ? "/{$app}/{$path}" : '/' . $path;
    }

    /**
     * Check if path is active.
     */
    public function active(string $path): string|false
    {
        try {
            if (!$this->container->has('router')) {
                return false;
            }

            $router = $this->container->get('router');
            $currentUri = $router->getUri();

            if ($currentUri === $path || $currentUri === '/' . $path) {
                return 'active';
            }

            if (($path === '' || $path === '/') && ($currentUri === '' || $currentUri === '/')) {
                return 'active';
            }
        } catch (\Throwable $e) {
            // Ignore
        }

        return false;
    }

    /**
     * Generate style tag.
     */
    public function style(string $path, array $options = []): string
    {
        $media = $options['media'] ?? 'all';

        if (!preg_match('#^https?://#', $path)) {
            $path = (defined('ROOT_URL') ? ROOT_URL : '') . ltrim($path, '/');
        }

        return '<link rel="stylesheet" href="' . $path . '" media="' . $media . '">';
    }

    /**
     * Generate script tag.
     */
    public function script(string $path, array $options = []): string
    {
        $defer = $options['defer'] ?? false;

        if (!preg_match('#^https?://#', $path)) {
            $path = (defined('ROOT_URL') ? ROOT_URL : '') . ltrim($path, '/');
        }

        $attrs = $defer ? ' defer' : '';

        return '<script src="' . $path . '"' . $attrs . '></script>';
    }

    /**
     * Generate img tag.
     */
    public function img(string $src, array $options = []): string
    {
        if (!preg_match('#^https?://#', $src) && !str_starts_with($src, 'data:')) {
            $src = (defined('ROOT_URL') ? ROOT_URL : '') . ltrim($src, '/');
        }

        $alt = $options['alt'] ?? '';
        $class = $options['class'] ?? '';

        return '<img src="' . $src . '" alt="' . $alt . '"' . ($class ? ' class="' . $class . '"' : '') . '>';
    }

    /**
     * Generate icon tag (Bootstrap Icons).
     */
    public function icon(string $name, array $options = []): string
    {
        $class = $options['class'] ?? '';

        return '<i class="bi bi-' . $name . '"' . ($class ? ' class="' . $class . '"' : '') . ' aria-hidden="true"></i>';
    }

    /**
     * Generate generic tag.
     */
    public function tag(string $tag, string $content = '', array $options = []): string
    {
        $attrs = $this->buildAttrs($options);

        if (in_array($tag, ['img', 'input', 'br', 'hr', 'meta', 'link'], true)) {
            return '<' . $tag . $attrs . '>';
        }

        return '<' . $tag . $attrs . '>' . $content . '</' . $tag . '>';
    }

    /**
     * Generate form tag.
     */
    public function form(string $method, string $action = '', array $options = []): string
    {
        $method = strtoupper($method);
        $attrs = $this->buildAttrs($options, ['method', 'action']);

        return '<form method="' . $method . '" action="' . $action . '"' . $attrs . '>';
    }

    /**
     * Generate input tag.
     */
    public function input(string $type, string $name, string $value = '', array $options = []): string
    {
        $attrs = $this->buildAttrs($options, ['type', 'name', 'value']);

        return '<input type="' . $type . '" name="' . $name . '" value="' . $value . '"' . $attrs . '>';
    }

    /**
     * Generate submit button.
     */
    public function submit(string $text, array $options = []): string
    {
        $attrs = $this->buildAttrs($options);

        return '<button type="submit"' . $attrs . '>' . $text . '</button>';
    }

    /**
     * Build HTML attributes string.
     */
    private function buildAttrs(array $options, array $exclude = []): string
    {
        $attrs = '';

        foreach ($options as $key => $value) {
            if (in_array($key, $exclude, true) || $value === '' || $value === null) {
                continue;
            }

            if (is_bool($value)) {
                if ($value) {
                    $attrs .= ' ' . $key;
                }
            } else {
                $attrs .= ' ' . $key . '="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '"';
            }
        }

        return $attrs;
    }
}
