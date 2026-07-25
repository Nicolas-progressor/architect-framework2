<?php

declare(strict_types=1);

namespace Architect\Services\Blueprint\Elements;

use Architect\Core\Container;
use Architect\Services\Blueprint\Contracts\BlueprintConfigInterface;
use Throwable;

/**
 * Renders MVC elements (widgets with controllers)
 */
final class MvcElementRenderer
{
    private Container $container;
    private BlueprintConfigInterface $config;

    public function __construct(Container $container, BlueprintConfigInterface $config)
    {
        $this->container = $container;
        $this->config = $config;
    }

    /**
     * Render MVC element
     */
    public function render(array $element, array $data = []): string
    {
        $module = $element['module'] ?? '';
        $controller = $element['controller'] ?? '';
        $action = $element['action'] ?? 'index';

        if (!$module || !$controller) {
            return '';
        }

        $resolved = $this->resolveControllerClass($module, $controller);
        $controllerClass = $resolved['class'];
        $isGlobal = $resolved['isGlobal'];

        if (!class_exists($controllerClass)) {
            return '';
        }

        try {
            $result = $this->executeController($controllerClass, $action, $data, $module, $isGlobal);
            return $result;
        } catch (Throwable $e) {
            return $this->handleError($e);
        }
    }

    /**
     * Resolve controller class name and load file
     */
    private function resolveControllerClass(string $module, string $controller): array
    {
        // Try app-specific module first
        if ($this->container->has('apps')) {
            $apps = $this->container->get('apps');
            $appName = $apps->getCurrentApp() ?? null;
            $appDir = $apps->getAppDir() ?? null;

            if ($appName && $appDir) {
                // Normalize app name (remove dashes for namespace)
                $appNameNormalized = str_replace('-', '', $appName);

                // Try widget file
                $widgetFile = $appDir . "modules/{$module}/widget/{$controller}.php";
                if (file_exists($widgetFile)) {
                    require_once $widgetFile;

                    // Try different class name variants
                    $variants = [
                        "app\\{$appNameNormalized}\\modules\\{$module}\\widget\\{$controller}",
                        "app\\{$appNameNormalized}\\modules\\{$module}\\widget\\" . ucfirst($controller),
                        "app\\{$appNameNormalized}\\modules\\{$module}\\widget\\" . strtolower($controller),
                    ];

                    foreach ($variants as $class) {
                        if (class_exists($class)) {
                            return ['class' => $class, 'isGlobal' => false];
                        }
                    }
                }
            }
        }

        // Try global module widget
        $globalWidgetFile = APP_DIR . "modules/{$module}/widget/{$controller}.php";
        if (file_exists($globalWidgetFile)) {
            require_once $globalWidgetFile;

            $variants = [
                "app\\modules\\{$module}\\widget\\{$controller}",
                "app\\modules\\{$module}\\widget\\" . ucfirst($controller),
                "{$module}\\widget\\{$controller}",
                "{$module}\\widget\\" . ucfirst($controller),
            ];

            foreach ($variants as $class) {
                if (class_exists($class)) {
                    return ['class' => $class, 'isGlobal' => true];
                }
            }
        }

        // Fallback - return expected class name even if not found (will log warning)
        $fallbackClass = "app\\modules\\{$module}\\widget\\" . ucfirst($controller);
        return ['class' => $fallbackClass, 'isGlobal' => true];
    }

    /**
     * Execute controller and return output
     */
    private function executeController(string $controllerClass, string $action, array $data, string $module, bool $isGlobal): string
    {
        // Pass module name and isGlobal to controller constructor
        $controllerInstance = new $controllerClass($this->container, $module, $isGlobal);

        // Set data
        foreach ($data as $key => $value) {
            if (method_exists($controllerInstance, 'set')) {
                $controllerInstance->set($key, $value);
            }
        }

        // Call data method
        $dataMethod = $action . '_app_data';
        if (method_exists($controllerInstance, $dataMethod)) {
            $controllerInstance->{$dataMethod}();
        }

        // Call output method
        $outputMethod = $action . '_app_output';
        if (method_exists($controllerInstance, $outputMethod)) {
            ob_start();
            $controllerInstance->{$outputMethod}();
            return ob_get_clean() ?: '';
        }

        return '';
    }

    /**
     * Handle rendering error
     */
    private function handleError(Throwable $e): string
    {
        if ($this->config->isDebug()) {
            return '<div class="error">Element error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }

        return '';
    }
}
