<?php

declare(strict_types=1);

namespace Architect\Services\Template\Renderer;

use Architect\Contracts\Core\ContainerInterface;
use Architect\Services\Template\Contracts\TemplateInterface;
use Architect\Services\Template\Contracts\TemplateRendererInterface;

/**
 * Renderer for native PHP templates.
 *
 * Proxies method calls to Template for backward compatibility
 * with templates using $this->element(), $this->getContent(), etc.
 */
final class PhpRenderer implements TemplateRendererInterface
{
    private ?TemplateInterface $template = null;
    private ?ContainerInterface $container = null;

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    public function supports(string $templatePath): bool
    {
        return file_exists($templatePath . 'template.php');
    }

    public function render(string $templatePath, array $data): string
    {
        $templateFile = $templatePath . 'template.php';

        if (!file_exists($templateFile)) {
            return $data['content'] ?? '';
        }

        $this->template = $data['template'] ?? null;

        // Extract common variables
        $content = $data['content'] ?? '';
        $title = $data['title'] ?? '';
        $elements = $data['elements'] ?? [];
        $routedElements = $data['routedElements'] ?? [];

        ob_start();
        include $templateFile;
        return ob_get_clean() ?: '';
    }

    /**
     * Proxy method calls to Template for backward compatibility.
     * Allows $this->element(), $this->getContent(), etc. in templates.
     */
    public function __call(string $method, array $arguments): mixed
    {
        if ($this->template !== null && method_exists($this->template, $method)) {
            return $this->template->{$method}(...$arguments);
        }

        return null;
    }

    /**
     * Proxy property access to Template.
     */
    public function __get(string $name): mixed
    {
        if ($this->template !== null && property_exists($this->template, $name)) {
            return $this->template->{$name};
        }

        return null;
    }
}
