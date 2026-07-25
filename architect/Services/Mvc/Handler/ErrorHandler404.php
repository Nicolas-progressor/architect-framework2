<?php

declare(strict_types=1);

namespace Architect\Services\Mvc\Handler;

use Architect\Core\Contracts\ContainerInterface;
use Architect\Services\Mvc\Handler\Contracts\ErrorHandler404Interface;
use Architect\Services\Mvc\Resolver\ModulePathResolver;

/**
 * 404 error handler implementation.
 *
 * Handles 404 errors with view rendering and fatal error handling.
 * Supports both application and global 404 views.
 *
 * @package Architect\Services\Mvc\Handler
 */
class ErrorHandler404 implements ErrorHandler404Interface
{
    /** @var ContainerInterface Dependency container */
    private ContainerInterface $container;

    /** @var ModulePathResolver Module path resolver */
    private ModulePathResolver $pathResolver;

    /** @var string Default 404 view template path */
    private string $defaultViewPath;

    /**
     * Create error handler instance.
     *
     * @param ContainerInterface $container Dependency container
     * @param ModulePathResolver $pathResolver Module path resolver
     */
    public function __construct(
        ContainerInterface $container,
        ModulePathResolver $pathResolver
    ) {
        $this->container = $container;
        $this->pathResolver = $pathResolver;
        $this->defaultViewPath = dirname(__FILE__) . '/View/404.php';
    }

    /**
     * @inheritdoc
     */
    public function handle(string $message = 'Page not found'): void
    {
        http_response_code(404);

        $view = $this->container->get('view');
        $template = $this->container->get('template');

        $content = $this->render404View($message, $view);
        $template->setContent($content);
    }

    /**
     * @inheritdoc
     */
    public function handleFatal(string $message = 'Page not found'): void
    {
        http_response_code(404);

        $errorMessage = $message . ': ' . ($_SERVER['REQUEST_URI'] ?? '/');

        $errors = $this->container->get('errors');
        $errors->display404($errorMessage);

        exit;
    }

    /**
     * @inheritdoc
     */
    public function hasApp404(): bool
    {
        $controllerPath = $this->pathResolver->getControllerPath('_404', '_404', false);
        return $controllerPath !== null;
    }

    /**
     * @inheritdoc
     */
    public function hasGlobal404(): bool
    {
        $controllerPath = $this->pathResolver->getControllerPath('_404', '_404', true);
        return $controllerPath !== null;
    }

    /**
     * Render 404 view.
     *
     * Tries app view, then global view, then default view.
     *
     * @param string $message Error message
     * @param mixed $view View service
     * @return string Rendered content
     */
    private function render404View(string $message, mixed $view): string
    {
        // Try app 404 view
        $app404View = $this->pathResolver->getViewPath('_404', false) . '_404.php';
        if (file_exists($app404View)) {
            return $view->render($app404View, ['message' => $message], false);
        }

        // Try global 404 view
        $global404View = $this->pathResolver->getViewPath('_404', true) . '_404.php';
        if (file_exists($global404View)) {
            return $view->render($global404View, ['message' => $message], false);
        }

        // Use default view
        return $view->render($this->defaultViewPath, ['message' => $message], false);
    }

    /**
     * Create 404 response data.
     *
     * @param string $message Error message
     * @return array Response data
     */
    public function createResponseData(string $message = 'Page not found'): array
    {
        return [
            'status' => 404,
            'message' => $message,
            'has_app_404' => $this->hasApp404(),
            'has_global_404' => $this->hasGlobal404(),
        ];
    }
}
