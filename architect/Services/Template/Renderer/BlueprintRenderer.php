<?php

declare(strict_types=1);

namespace Architect\Services\Template\Renderer;

use Architect\Services\Template\Contracts\BlueprintAdapterInterface;
use Architect\Services\Template\Contracts\TemplateRendererInterface;

/**
 * Renderer for Blueprint templates.
 */
final class BlueprintRenderer implements TemplateRendererInterface
{
    public function __construct(
        private readonly BlueprintAdapterInterface $adapter
    ) {}

    public function supports(string $templatePath): bool
    {
        return $this->adapter->isAvailable()
            && file_exists($templatePath . 'template.blu');
    }

    public function render(string $templatePath, array $data): string
    {
        $templateName = $data['templateName'] ?? '';

        if ($templateName === '') {
            return $data['content'] ?? '';
        }

        // Set template context
        $this->adapter->setTemplate($templatePath, $templateName);

        // Add callbacks to data
        $data['element'] = $data['elementCallback'] ?? function () {};
        $data['widget'] = $data['element'];
        $data['asset'] = $this->createAssetCallback();

        return $this->adapter->render($templateName . '/template', $data);
    }

    private function createAssetCallback(): callable
    {
        return function (string $path): string {
            return '/assets/' . ltrim($path, '/');
        };
    }
}
