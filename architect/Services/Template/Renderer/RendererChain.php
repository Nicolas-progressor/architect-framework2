<?php

declare(strict_types=1);

namespace Architect\Services\Template\Renderer;

use Architect\Services\Template\Contracts\TemplateRendererInterface;

/**
 * Chain of renderers - tries each until one supports.
 */
final class RendererChain implements TemplateRendererInterface
{
    /**
     * @var TemplateRendererInterface[]
     */
    private array $renderers = [];

    public function __construct(
        TemplateRendererInterface ...$renderers
    ) {
        $this->renderers = $renderers;
    }

    public function add(TemplateRendererInterface $renderer): void
    {
        $this->renderers[] = $renderer;
    }

    public function supports(string $templatePath): bool
    {
        foreach ($this->renderers as $renderer) {
            if ($renderer->supports($templatePath)) {
                return true;
            }
        }
        return false;
    }

    public function render(string $templatePath, array $data): string
    {
        foreach ($this->renderers as $renderer) {
            if ($renderer->supports($templatePath)) {
                return $renderer->render($templatePath, $data);
            }
        }

        // Fallback - return content
        return $data['content'] ?? '';
    }
}
