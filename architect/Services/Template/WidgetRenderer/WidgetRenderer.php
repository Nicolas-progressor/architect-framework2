<?php

declare(strict_types=1);

namespace Architect\Services\Template\WidgetRenderer;

use Architect\Contracts\Core\ContainerInterface;
use Architect\Services\App\Contracts\AppsServiceInterface;
use Architect\Services\Template\Contracts\WidgetRendererInterface;

/**
 * Renders widgets (MVC elements with controllers).
 *
 * Widget classes extend the base Controller and require Container
 * for their operation. This is explicit DI, not Service Locator.
 */
final class WidgetRenderer implements WidgetRendererInterface
{
    private const WIDGET_DIR = 'widget';

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly AppsServiceInterface $apps
    ) {}

    public function render(string $module, string $controller, string $action = 'create'): string
    {
        $classInfo = $this->resolveClass($module, $controller);

        if ($classInfo === null) {
            return '<!-- widget not found: ' . $module . '/' . $controller . ' -->';
        }

        $result = $this->executeWidget($classInfo['class'], $action, $module, $classInfo['isGlobal']);

        return $result;
    }

    public function exists(string $module, string $controller): bool
    {
        return $this->resolveClass($module, $controller) !== null;
    }

    /**
     * Resolve widget class.
     */
    private function resolveClass(string $module, string $controller): ?array
    {
        $appDir = $this->apps->getAppDir();
        $appName = $this->apps->getCurrentApp();

        // 1. Try app-specific widget
        $appWidgetFile = $appDir . "modules/{$module}/widget/{$controller}.php";
        if (file_exists($appWidgetFile)) {
            // Namespace is app\{appName}\modules\{module}\widget\{controller}
            // Example: app\home\modules\navbar\widget\navbar
            $appClass = 'app\\' . str_replace('-', '', $appName) . "\\modules\\{$module}\\widget\\{$controller}";
            if (class_exists($appClass)) {
                return ['class' => $appClass, 'isGlobal' => false];
            }
            // Try lowercase controller
            $appClassLower = 'app\\' . str_replace('-', '', $appName) . "\\modules\\{$module}\\widget\\" . strtolower($controller);
            if (class_exists($appClassLower)) {
                return ['class' => $appClassLower, 'isGlobal' => false];
            }
            // Try with ucfirst
            $appClassUcfirst = 'app\\' . str_replace('-', '', $appName) . "\\modules\\{$module}\\widget\\" . ucfirst(strtolower($controller));
            if (class_exists($appClassUcfirst)) {
                return ['class' => $appClassUcfirst, 'isGlobal' => false];
            }
        }

        // 2. Try global widget
        $globalWidgetFile = APP_DIR . "modules/{$module}/widget/{$controller}.php";
        if (file_exists($globalWidgetFile)) {
            $globalClass = "app\\modules\\{$module}\\widget\\{$controller}";
            if (class_exists($globalClass)) {
                return ['class' => $globalClass, 'isGlobal' => true];
            }
            // Try lowercase
            $globalClassLower = "app\\modules\\{$module}\\widget\\" . strtolower($controller);
            if (class_exists($globalClassLower)) {
                return ['class' => $globalClassLower, 'isGlobal' => true];
            }
            // Try with ucfirst
            $globalClassUcfirst = "app\\modules\\{$module}\\widget\\" . ucfirst(strtolower($controller));
            if (class_exists($globalClassUcfirst)) {
                return ['class' => $globalClassUcfirst, 'isGlobal' => true];
            }
        }

        return null;
    }

    /**
     * Execute widget and return output.
     */
    private function executeWidget(string $className, string $action, string $module, bool $isGlobal): string
    {
        // Widget controllers require Container for their operation
        $widget = new $className($this->container, $module, $isGlobal);

        $dataMethod = "{$action}_app_data";
        $outputMethod = "{$action}_app_output";

        if (method_exists($widget, $dataMethod)) {
            $widget->{$dataMethod}();
        }

        if (!method_exists($widget, $outputMethod)) {
            return '';
        }

        ob_start();
        $widget->{$outputMethod}();
        return ob_get_clean() ?: '';
    }

}
