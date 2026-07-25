<?php

declare(strict_types=1);

namespace Architect\Services\Blueprint;

use Blueprint\Engine\Blueprint;
use Blueprint\Engine\BlueprintExtension as BlueprintExtensionInterface;
use Architect\Services\Blueprint\Contracts\ElementRendererInterface;

/**
 * Blueprint extension for element/widget rendering
 */
final class BlueprintExtension implements BlueprintExtensionInterface
{
    private ElementRendererInterface $elementRenderer;

    public function __construct(ElementRendererInterface $elementRenderer)
    {
        $this->elementRenderer = $elementRenderer;
    }
    
    /**
     * Register extension with Blueprint
     */
    public function register(Blueprint $blueprint): void
    {
        $renderer = $this->elementRenderer;
        
        // Register element function
        $blueprint->registerFunction('element', function (string $name, array $data = []) use ($renderer) {
            return $renderer->render($name, $data);
        });
        
        // Register widget function (alias for element)
        $blueprint->registerFunction('widget', function (string $name, array $data = []) use ($renderer) {
            return $renderer->render($name, $data);
        });
    }
    
    /**
     * Get element renderer
     */
    public function getElementRenderer(): ElementRendererInterface
    {
        return $this->elementRenderer;
    }
}
