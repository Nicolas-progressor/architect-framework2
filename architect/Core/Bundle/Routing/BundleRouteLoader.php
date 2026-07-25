<?php

declare(strict_types=1);

namespace Architect\Core\Bundle\Routing;

use Architect\Contracts\BundleInterface;

/**
 * Loads routes from bundles.
 */
class BundleRouteLoader
{
    /**
     * Load routes from a bundle.
     *
     * @param BundleInterface $bundle
     * @return array
     */
    public function load(BundleInterface $bundle): array
    {
        $routes = [];

        // Load from bundle's routing configuration
        $bundleRoutes = $this->loadBundleRoutes($bundle);
        if (!empty($bundleRoutes)) {
            $routes = array_merge($routes, $bundleRoutes);
        }

        // Load from bundle's annotation routes
        $annotationRoutes = $this->loadAnnotationRoutes($bundle);
        if (!empty($annotationRoutes)) {
            $routes = array_merge($routes, $annotationRoutes);
        }

        return $routes;
    }

    /**
     * Load routes from bundle's routing configuration.
     *
     * @param BundleInterface $bundle
     * @return array
     */
    private function loadBundleRoutes(BundleInterface $bundle): array
    {
        $reflection = new \ReflectionClass($bundle);
        $bundleDir = dirname($reflection->getFileName());

        $routes = [];

        // Try Resources/config/routes.json
        $routesFile = $bundleDir . '/Resources/config/routes.json';
        if (file_exists($routesFile)) {
            $routes = array_merge($routes, $this->loadRoutesFromFile($routesFile));
        }

        // Try config/routes.json
        $routesFile = $bundleDir . '/config/routes.json';
        if (file_exists($routesFile)) {
            $routes = array_merge($routes, $this->loadRoutesFromFile($routesFile));
        }

        // Try routes.json
        $routesFile = $bundleDir . '/routes.json';
        if (file_exists($routesFile)) {
            $routes = array_merge($routes, $this->loadRoutesFromFile($routesFile));
        }

        return $routes;
    }

    /**
     * Load routes from a JSON file.
     *
     * @param string $filePath
     * @return array
     */
    private function loadRoutesFromFile(string $filePath): array
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return [];
        }

        $data = json_decode($content, true);
        if (!is_array($data)) {
            return [];
        }

        return $data;
    }

    /**
     * Load routes from annotations in bundle controllers.
     *
     * @param BundleInterface $bundle
     * @return array
     */
    private function loadAnnotationRoutes(BundleInterface $bundle): array
    {
        $reflection = new \ReflectionClass($bundle);
        $bundleDir = dirname($reflection->getFileName());

        $routes = [];

        // Scan for controllers
        $controllerDirs = [
            $bundleDir . '/Controller',
            $bundleDir . '/Controllers',
            $bundleDir . '/Resources/Controller',
            $bundleDir . '/Resources/Controllers',
        ];

        foreach ($controllerDirs as $controllerDir) {
            if (is_dir($controllerDir)) {
                $controllerRoutes = $this->scanControllersForRoutes($controllerDir, $bundle);
                $routes = array_merge($routes, $controllerRoutes);
            }
        }

        return $routes;
    }

    /**
     * Scan controllers for route annotations.
     *
     * @param string $controllerDir
     * @param BundleInterface $bundle
     * @return array
     */
    private function scanControllersForRoutes(string $controllerDir, BundleInterface $bundle): array
    {
        $routes = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($controllerDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $controllerRoutes = $this->parseControllerRoutes($file->getPathname(), $bundle);
                $routes = array_merge($routes, $controllerRoutes);
            }
        }

        return $routes;
    }

    /**
     * Parse routes from a controller file.
     *
     * @param string $filePath
     * @param BundleInterface $bundle
     * @return array
     */
    private function parseControllerRoutes(string $filePath, BundleInterface $bundle): array
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return [];
        }

        $routes = [];
        $className = $this->getClassNameFromFile($filePath);

        if (!$className || !class_exists($className)) {
            return [];
        }

        $reflection = new \ReflectionClass($className);

        // Get class-level route annotation
        $classDoc = $reflection->getDocComment();
        if ($classDoc) {
            $classRoute = $this->parseRouteAnnotation($classDoc);
            if ($classRoute) {
                $classRoute['controller'] = $this->getControllerName($className);
                $routes[] = $classRoute;
            }
        }

        // Get method-level route annotations
        foreach ($reflection->getMethods() as $method) {
            $methodDoc = $method->getDocComment();
            if ($methodDoc) {
                $methodRoute = $this->parseRouteAnnotation($methodDoc);
                if ($methodRoute) {
                    $methodRoute['controller'] = $this->getControllerName($className);
                    $methodRoute['action'] = $method->getName();
                    $routes[] = $methodRoute;
                }
            }
        }

        return $routes;
    }

    /**
     * Parse route annotation from doc comment.
     *
     * @param string $docComment
     * @return array|null
     */
    private function parseRouteAnnotation(string $docComment): ?array
    {
        // Simple annotation parsing for @Route
        if (preg_match('/@Route\s*\(\s*["\']([^"\']+)["\']/', $docComment, $matches)) {
            $route = [
                'path' => $matches[1],
                'methods' => ['GET'],
            ];

            // Parse methods
            if (preg_match('/methods\s*=\s*\{([^}]+)\}/', $docComment, $methodMatches)) {
                $methods = array_map('trim', explode(',', $methodMatches[1]));
                $methods = array_map(function ($method) {
                    return trim($method, ' "\'');
                }, $methods);
                $route['methods'] = $methods;
            }

            // Parse name
            if (preg_match('/name\s*=\s*["\']([^"\']+)["\']/', $docComment, $nameMatches)) {
                $route['name'] = $nameMatches[1];
            }

            return $route;
        }

        return null;
    }

    /**
     * Get class name from file.
     *
     * @param string $filePath
     * @return string|null
     */
    private function getClassNameFromFile(string $filePath): ?string
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return null;
        }

        $tokens = token_get_all($content);
        $namespace = '';
        $className = '';

        for ($i = 0; isset($tokens[$i]); $i++) {
            if ($tokens[$i][0] === T_NAMESPACE) {
                for ($j = $i + 1; isset($tokens[$j]); $j++) {
                    if ($tokens[$j][0] === T_STRING) {
                        $namespace .= '\\' . $tokens[$j][1];
                    } elseif ($tokens[$j] === '{' || $tokens[$j] === ';') {
                        break;
                    }
                }
            }

            if ($tokens[$i][0] === T_CLASS) {
                for ($j = $i + 1; isset($tokens[$j]); $j++) {
                    if ($tokens[$j] === '{') {
                        $className = ltrim($namespace . '\\' . $className, '\\');
                        return $className;
                    }
                    if ($tokens[$j][0] === T_STRING) {
                        $className = $tokens[$j][1];
                    }
                }
            }
        }

        return null;
    }

    /**
     * Get controller name from class name.
     *
     * @param string $className
     * @return string
     */
    private function getControllerName(string $className): string
    {
        $parts = explode('\\', $className);
        $shortName = end($parts);

        // Remove "Controller" suffix if present
        if (str_ends_with($shortName, 'Controller')) {
            $shortName = substr($shortName, 0, -10);
        }

        return strtolower($shortName);
    }

    /**
     * Load routes from all bundles.
     *
     * @param array $bundles
     * @return array
     */
    public function loadAll(array $bundles): array
    {
        $allRoutes = [];

        foreach ($bundles as $bundle) {
            $bundleRoutes = $this->load($bundle);
            $allRoutes[$bundle->getName()] = $bundleRoutes;
        }

        return $allRoutes;
    }

    /**
     * Merge bundle routes into application routes.
     *
     * @param array $bundleRoutes
     * @param array $appRoutes
     * @return array
     */
    public function mergeRoutes(array $bundleRoutes, array $appRoutes): array
    {
        foreach ($bundleRoutes as $bundleName => $routes) {
            foreach ($routes as $route) {
                // Add bundle prefix to route name if not already present
                if (isset($route['name']) && !str_contains($route['name'], '.')) {
                    $route['name'] = $bundleName . '.' . $route['name'];
                }

                $appRoutes[] = $route;
            }
        }

        return $appRoutes;
    }
}
