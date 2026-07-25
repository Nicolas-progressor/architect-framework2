<?php

declare(strict_types=1);

namespace Architect\Services\Template;

use Architect\Services\Routing\Contracts\RouterInterface;
use Architect\Services\Template\Contracts\ElementLoaderInterface;
use Architect\Services\Template\Contracts\PathResolverInterface;
use Architect\Services\Template\Contracts\TemplateConfigLoaderInterface;
use Architect\Services\Template\Contracts\TemplateInterface;
use Architect\Services\Template\Contracts\WidgetRendererInterface;
use Architect\Services\Template\Renderer\RendererChain;

/**
 * Template service for managing page templates.
 */
final class Template implements TemplateInterface
{
    private ?string $templatePath = null;
    private ?string $templateName = null;
    private ?string $content = null;
    private string $title = '';
    private bool $enabled = true;
    private bool $locked = false;

    private array $elements = [];
    private array $routedElements = [];
    private bool $elementsLoaded = false;

    public function __construct(
        private readonly RouterInterface $router,
        private readonly PathResolverInterface $pathResolver,
        private readonly ElementLoaderInterface $elementLoader,
        private readonly WidgetRendererInterface $widgetRenderer,
        private readonly TemplateConfigLoaderInterface $configLoader,
        private readonly RendererChain $rendererChain
    ) {}

    public function boot(): void
    {
        $defaultTemplate = $this->configLoader->getDefaultTemplate();

        if ($defaultTemplate !== null) {
            $this->setTemplate($defaultTemplate);
        }
    }

    // ============================================
    // Template Management
    // ============================================

    public function setTemplate(string $name): void
    {
        if ($this->isLocked()) {
            return;
        }

        $path = $this->pathResolver->resolveTemplatePath($name);

        if ($path !== null) {
            $this->applyTemplate($path, $name);
        }
    }

    public function setTemplateFromApp(string $appName, string $templateName): void
    {
        if ($this->isLocked()) {
            return;
        }

        $path = $this->pathResolver->resolveTemplatePathFromApp($appName, $templateName);

        if ($path !== null) {
            $this->applyTemplate($path, $templateName);
        }
    }

    public function setTemplatePath(string $path): void
    {
        if ($this->isLocked()) {
            return;
        }
        
        $this->applyTemplate($path, basename($path));
    }

    private function applyTemplate(string $path, string $name): void
    {
        $this->templatePath = rtrim($path, '/') . '/';
        $this->templateName = $name;
        $this->enabled = true;
        $this->elementsLoaded = false;
        $this->elements = $this->elementLoader->load($this->templatePath);
    }

    // ============================================
    // Rendering
    // ============================================

    public function render(): void
    {
        echo $this->renderToString();
    }

    /**
     * Render template to string.
     */
    public function renderToString(): string
    {
        if (!$this->isEnabled()) {
            return $this->content ?? '';
        }

        $this->ensureElementsLoaded();

        $data = $this->getTemplateData();
        $data['template'] = $this;
        $data['elementCallback'] = $this->createElementCallback();

        return $this->rendererChain->render($this->templatePath, $data);
    }

    public function element(string $name): string
    {
        $this->ensureElementsLoaded();

        $element = $this->routedElements[$name] ?? $this->elements[$name] ?? null;

        if ($element === null) {
            return '<!-- element not found: ' . $name . ' -->';
        }

        $module = $element['module'] ?? 'home';
        $controller = $element['controller'] ?? $module;
        $action = $element['action'] ?? 'create';

        return $this->widgetRenderer->render($module, $controller, $action);
    }

    /**
     * Display element directly (outputs to buffer).
     * For backward compatibility with templates using <?php $this->element('name'); ?>
     */
    public function displayElement(string $name): void
    {
        echo $this->element($name);
    }

    // ============================================
    // Callbacks
    // ============================================

    private function createElementCallback(): callable
    {
        return function(string $name): string {
            return $this->element($name);
        };
    }

    // ============================================
    // Data
    // ============================================

    private function getTemplateData(): array
    {
        return [
            'content' => $this->content ?? '',
            'title' => $this->title,
            'templateName' => $this->templateName,
            'elements' => $this->elements,
            'routedElements' => $this->routedElements,
        ];
    }

    private function ensureElementsLoaded(): void
    {
        if ($this->elementsLoaded || $this->templatePath === null) {
            return;
        }

        $this->routedElements = $this->elementLoader->loadRouted(
            $this->templatePath,
            $this->router->getModule(),
            $this->router->getController(),
            $this->router->getAction()
        );

        $this->elementsLoaded = true;
    }

    // ============================================
    // State Management (TemplateStateInterface)
    // ============================================

    public function isEnabled(): bool
    {
        return $this->enabled && $this->templatePath !== null;
    }

    public function isLocked(): bool
    {
        return $this->locked;
    }

    public function disable(): void
    {
        $this->enabled = false;
    }

    public function enable(): void
    {
        if (!$this->locked) {
            $this->enabled = true;
        }
    }

    public function lock(): void
    {
        $this->locked = true;
    }

    // ============================================
    // Content Management (TemplateContentInterface)
    // ============================================

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    // ============================================
    // Elements Management (TemplateElementsInterface)
    // ============================================

    public function getElements(): array
    {
        return $this->elements;
    }

    public function getRoutedElements(): array
    {
        $this->ensureElementsLoaded();
        return $this->routedElements;
    }

    // ============================================
    // Getters
    // ============================================

    public function getTemplatePath(): ?string
    {
        return $this->templatePath;
    }

    public function getTemplateName(): ?string
    {
        return $this->templateName;
    }
}

