<?php

declare(strict_types=1);

namespace Architect\Services\Mvc;

use Architect\Services\Blueprint\BlueprintService;
use Architect\Services\Mvc\Contracts\ViewInterface;
use Architect\Services\Mvc\Exceptions\ViewNotFoundException;
use Architect\Support\AbstractService;
use Blueprint\Engine\Blueprint;

/**
 * View service for rendering templates.
 *
 * Supports both PHP templates and Blueprint templates (.blu files).
 * Handles template discovery, rendering, and data management.
 */
class View extends AbstractService implements ViewInterface
{
    /** @var string Template directory path */
    private string $templateDir = '';

    /** @var array View data for templates */
    private array $data = [];

    /** @var BlueprintService|null Blueprint service instance */
    private ?BlueprintService $blueprintService = null;

    /** @var Blueprint|null Blueprint engine instance */
    private ?Blueprint $blueprint = null;

    /** @var string Module path for Blueprint context */
    private string $modulePath = '';

    /**
     * Set template directory.
     *
     * @param string $dir Directory path
     */
    public function setTemplateDir(string $dir): void
    {
        $this->templateDir = rtrim($dir, '/\\');

        if ($this->blueprintService !== null && !empty($this->modulePath)) {
            $this->blueprintService->setModuleContext($this->modulePath);
        }
    }

    /**
     * Get template directory.
     *
     * @return string
     */
    public function getTemplateDir(): string
    {
        return $this->templateDir;
    }

    /**
     * Set module path for Blueprint context.
     *
     * @param string $path Module path
     */
    public function setModulePath(string $path): void
    {
        $this->modulePath = rtrim($path, '/\\');
    }

    /**
     * Set Blueprint service.
     *
     * @param BlueprintService|Blueprint $blueprint Blueprint instance or service
     */
    public function setBlueprint(BlueprintService|Blueprint $blueprint): void
    {
        if ($blueprint instanceof BlueprintService) {
            $this->blueprintService = $blueprint;
            $this->blueprint = $blueprint->getBlueprint();
        } else {
            $this->blueprint = $blueprint;
        }

        if (!empty($this->modulePath)) {
            $this->blueprintService?->setModuleContext($this->modulePath);
        }
    }

    /**
     * Check if Blueprint is available.
     *
     * @return bool
     */
    public function hasBlueprint(): bool
    {
        return $this->blueprint !== null;
    }

    /**
     * Set view data.
     *
     * @param array $data Data to merge with existing data
     */
    public function setData(array $data): void
    {
        $this->data = array_merge($this->data, $data);
    }

    /**
     * Render view template.
     *
     * @param string $template Template name or path
     * @param array $data Template data
     * @param bool $setContent Whether to set content in template service
     * @return string Rendered content
     * @throws ViewNotFoundException If template not found
     */
    public function render(string $template, array $data = [], bool $setContent = true): string
    {
        $this->data = array_merge($this->data, $data);

        if ($this->blueprint && $this->isBlueprintTemplate($template)) {
            return $this->renderBlueprint($template, $data, $setContent);
        }

        $templateFile = $this->resolveTemplatePath($template);

        if (!file_exists($templateFile)) {
            throw ViewNotFoundException::create($template, $this->templateDir);
        }

        $content = $this->renderPhpTemplate($templateFile);

        if ($setContent) {
            $templateService = $this->get('template');
            $templateService->setContent($content);
        }

        return $content;
    }

    /**
     * Display view template (output directly).
     *
     * @param string $template Template name or path
     * @param array $data Template data
     * @throws ViewNotFoundException If template not found
     */
    public function display(string $template, array $data = []): void
    {
        $this->data = array_merge($this->data, $data);

        if ($this->blueprint && $this->isBlueprintTemplate($template)) {
            echo $this->renderBlueprint($template, $data, false);
            return;
        }

        $templateFile = $this->resolveTemplatePath($template);

        if (!file_exists($templateFile)) {
            throw ViewNotFoundException::create($template, $this->templateDir);
        }

        echo $this->renderPhpTemplate($templateFile);
    }

    /**
     * Clear view data.
     */
    public function clear(): void
    {
        $this->data = [];
    }

    /**
     * Resolve template file path.
     *
     * @param string $template Template name or path
     * @return string Resolved file path
     */
    private function resolveTemplatePath(string $template): string
    {
        if ($this->isAbsolutePath($template)) {
            return $template;
        }

        $templateFile = $this->templateDir . '/' . $template . '.php';

        // Try .blu if .php not found and Blueprint available
        if (!file_exists($templateFile) && $this->blueprint !== null) {
            $bluFile = $this->templateDir . '/' . $template . '.blu';
            if (file_exists($bluFile)) {
                return $bluFile;
            }
        }

        return $templateFile;
    }

    /**
     * Render PHP template file.
     *
     * @param string $templateFile Absolute template path
     * @return string Rendered content
     */
    private function renderPhpTemplate(string $templateFile): string
    {
        extract($this->data);

        ob_start();
        include $templateFile;
        return ob_get_clean();
    }

    /**
     * Check if template is a Blueprint template.
     *
     * @param string $template Template name
     * @return bool
     */
    private function isBlueprintTemplate(string $template): bool
    {
        if ($this->blueprint === null) {
            return false;
        }

        if (str_ends_with($template, '.blu')) {
            return true;
        }

        $bluFile = $this->templateDir . '/' . $template . '.blu';
        return file_exists($bluFile);
    }

    /**
     * Check if path is absolute.
     *
     * @param string $template Path to check
     * @return bool
     */
    private function isAbsolutePath(string $template): bool
    {
        return str_starts_with($template, '/')
            || str_starts_with($template, '\\\\')
            || str_contains($template, ':\\')
            || str_ends_with($template, '.php');
    }

    /**
     * Render Blueprint template.
     *
     * @param string $template Template name
     * @param array $data Template data
     * @param bool $setContent Whether to set content in template service
     * @return string Rendered content
     */
    private function renderBlueprint(string $template, array $data, bool $setContent): string
    {
        $templateName = $template;
        if (str_ends_with($template, '.blu')) {
            $templateName = substr($template, 0, -4);
        }

        $this->registerModulePaths();

        $content = $this->blueprint->render($templateName, array_merge($this->data, $data));

        if ($setContent) {
            $templateService = $this->get('template');
            $templateService->setContent($content);
        }

        return $content;
    }

    /**
     * Register module paths in Blueprint loader.
     */
    private function registerModulePaths(): void
    {
        if ($this->blueprint === null) {
            return;
        }

        $loader = $this->blueprint->getLoader();

        if ($loader === null) {
            return;
        }

        if (!empty($this->templateDir) && is_dir($this->templateDir)) {
            $loader->addPath($this->templateDir);

            $elementsDir = $this->templateDir . '/elements/';
            if (is_dir($elementsDir)) {
                $loader->addPath($elementsDir);
            }
        }

        if ($this->blueprintService !== null && !empty($this->modulePath)) {
            $this->blueprintService->setModuleContext($this->modulePath);
        }
    }
}
