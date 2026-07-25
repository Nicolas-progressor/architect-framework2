<?php

declare(strict_types=1);

namespace Architect\Services\Template\Blueprint;

use Architect\Services\Template\Contracts\BlueprintAdapterInterface;
use Blueprint\Engine\Blueprint;

/**
 * Adapter for Blueprint template engine.
 */
final class BlueprintAdapter implements BlueprintAdapterInterface
{
    private ?Blueprint $blueprint = null;
    private bool $initialized = false;
    private ?string $templatePath = null;
    private ?string $templateName = null;

    public function __construct(
        private readonly ?Blueprint $blueprintInstance = null
    ) {
        if ($blueprintInstance !== null) {
            $this->blueprint = $blueprintInstance;
            $this->initialized = true;
        }
    }

    public function isAvailable(): bool
    {
        if ($this->blueprint !== null) {
            return true;
        }

        return $this->tryInitBlueprint();
    }

    public function setTemplate(string $path, string $name): void
    {
        $this->templatePath = rtrim($path, '/') . '/';
        $this->templateName = $name;
    }

    public function setPaths(array $paths): void
    {
        if ($this->blueprint === null) {
            return;
        }

        $loader = $this->blueprint->getLoader();

        foreach ($paths as $path) {
            $loader->addPath($path);
        }
    }

    public function render(string $template, array $data): string
    {
        if (!$this->isAvailable()) {
            return '';
        }

        $this->applyTemplateContext();

        return $this->blueprint->render($template, $data);
    }

    /**
     * Try to initialize Blueprint from class autoloading.
     */
    private function tryInitBlueprint(): bool
    {
        if ($this->initialized) {
            return $this->blueprint !== null;
        }

        $this->initialized = true;

        // Check if Blueprint class exists (installed via composer)
        if (!class_exists(Blueprint::class)) {
            return false;
        }

        try {
            $this->blueprint = new Blueprint();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Apply current template context to Blueprint.
     */
    private function applyTemplateContext(): void
    {
        if ($this->blueprint === null || $this->templateName === null) {
            return;
        }

        if (method_exists($this->blueprint, 'setCurrentTemplate')) {
            $templateFile = $this->templateName . '/template';
            $this->blueprint->setCurrentTemplate($templateFile);
        }
    }
}
