<?php

declare(strict_types=1);

namespace Architect\Services\App;

use Architect\Core\Contracts\ContainerInterface;
use Architect\Core\Contracts\StatementInterface;
use Architect\Services\App\Contracts\AppBootstrapInterface;
use Architect\Services\App\Contracts\AppDescriptor;
use Architect\Services\App\Contracts\AppsServiceInterface;
use Architect\Services\Config\Contracts\ConfigLoaderInterface;
use Architect\Services\Routing\Contracts\RouterInterface;
use Architect\Support\AbstractService;
use Psr\Log\LoggerInterface;

/**
 * Apps service for managing multiple applications.
 *
 * Responsibilities:
 * - Registry of available applications
 * - Current application resolution based on URL
 * - Application switching
 */
class Apps extends AbstractService implements AppsServiceInterface
{
    private string $currentApp = '';
    private string $appDir = '';
    private string $defaultApp = 'home';
    private string $appsBaseDir = '';

    /** @var array<string, AppDescriptor> */
    private array $apps = [];

    /** @var array<string, mixed> */
    private array $appConfig = [];

    private ?AppBootstrapInterface $appBootstrap = null;

    private AppConfigLoader $configLoader;
    private AppBootstrapLoader $bootstrapLoader;

    /** @var callable|null Lazy router resolver to avoid circular dependency */
    private $routerResolver = null;

    /**
     * Create Apps service.
     *
     * Note: Router is injected lazily via setRouterResolver() to avoid circular dependency.
     */
    public function __construct(
        ContainerInterface $container,
        private readonly StatementInterface $statement,
        private readonly ConfigLoaderInterface $configLoaderService,
        private readonly ?LoggerInterface $logger = null,
    ) {
        parent::__construct($container);

        $this->configLoader = new AppConfigLoader($logger);
        $this->bootstrapLoader = new AppBootstrapLoader($statement, $logger);
    }

    /**
     * Set router resolver for lazy loading (breaks circular dependency).
     *
     * @param callable $resolver Function that returns RouterInterface
     */
    public function setRouterResolver(callable $resolver): void
    {
        $this->routerResolver = $resolver;
    }

    /**
     * Get router instance (lazy resolution).
     */
    private function getRouter(): RouterInterface
    {
        if ($this->routerResolver === null) {
            throw new \RuntimeException('Router resolver not set. Call setRouterResolver() before using router.');
        }

        return ($this->routerResolver)();
    }

    /**
     * Boot the service.
     */
    public function boot(): void
    {
        $appsConfig = $this->configLoaderService->load('apps');

        $this->appsBaseDir = $this->resolveAppsBaseDir();
        $this->defaultApp = $appsConfig->get('default', 'home');
        $this->loadApps($appsConfig->get('apps', []));

        $this->statement->on('core_init', function ($container) {
            $this->resolveCurrentApp();
            $this->loadCurrentApp();
        }, 1);
    }

    /**
     * Resolve apps base directory.
     */
    private function resolveAppsBaseDir(): string
    {
        if (defined('APP_DIR')) {
            return APP_DIR . 'apps/';
        }

        $fallback = dirname(__DIR__, 3) . '/app/apps/';
        $this->logger?->debug('APP_DIR not defined, using fallback path', ['path' => $fallback]);

        return $fallback;
    }

    /**
     * Load applications from config.
     *
     * @param array<string, string> $appsConfig
     */
    private function loadApps(array $appsConfig): void
    {
        foreach ($appsConfig as $name => $folder) {
            $appPath = $this->appsBaseDir . $folder . '/';
            $resolvedPath = is_dir($appPath) ? $appPath : '';

            if ($resolvedPath === '') {
                $this->logger?->warning('Application directory not found', [
                    'app' => $name,
                    'expected_path' => $appPath,
                ]);
            }

            $this->apps[$name] = new AppDescriptor(
                name: $name,
                folder: $folder,
                path: $resolvedPath
            );
        }
    }

    /**
     * Resolve current application from URL.
     */
    private function resolveCurrentApp(): void
    {
        $router = $this->getRouter();
        $firstSegment = $router->segment(1);

        if ($firstSegment !== '' && isset($this->apps[$firstSegment]) && $this->apps[$firstSegment]->exists()) {
            $this->currentApp = $firstSegment;
            $this->appDir = $this->apps[$firstSegment]->getPath();
            return;
        }

        $this->currentApp = $this->defaultApp;
        $this->appDir = $this->getDefaultAppPath();
    }

    /**
     * Load current application config and bootstrap.
     */
    private function loadCurrentApp(): void
    {
        $this->appConfig = $this->configLoader->load($this->appDir);
        $this->appBootstrap = $this->bootstrapLoader->load($this->appDir, $this->currentApp);
    }

    /**
     * Get default application path.
     */
    private function getDefaultAppPath(): string
    {
        if (isset($this->apps[$this->defaultApp]) && $this->apps[$this->defaultApp]->exists()) {
            return $this->apps[$this->defaultApp]->getPath();
        }

        return $this->appsBaseDir . $this->defaultApp . '/';
    }

    /**
     * Get current application name.
     */
    public function getCurrentApp(): string
    {
        return $this->currentApp;
    }

    /**
     * Get current application directory.
     */
    public function getAppDir(): string
    {
        return $this->appDir;
    }

    /**
     * Get base directory for all applications.
     */
    public function getAppsBaseDir(): string
    {
        return $this->appsBaseDir;
    }

    /**
     * Get default application name.
     */
    public function getDefaultApp(): string
    {
        return $this->defaultApp;
    }

    /**
     * Get all registered applications.
     *
     * @return array<string, AppDescriptor>
     */
    public function getApps(): array
    {
        return $this->apps;
    }

    /**
     * Check if application exists.
     */
    public function hasApp(string $name): bool
    {
        return isset($this->apps[$name]) && $this->apps[$name]->exists();
    }

    /**
     * Get application descriptor by name.
     */
    public function getAppDescriptor(string $name): ?AppDescriptor
    {
        return $this->apps[$name] ?? null;
    }

    /**
     * Switch to another application.
     */
    public function switchApp(string $appName): void
    {
        if (!$this->hasApp($appName)) {
            $this->logger?->warning('Attempted to switch to non-existent app', ['app' => $appName]);
            return;
        }

        $this->currentApp = $appName;
        $this->appDir = $this->apps[$appName]->getPath();

        $this->loadCurrentApp();
    }

    /**
     * Get current application configuration.
     *
     * @return array<string, mixed>
     */
    public function getAppConfig(): array
    {
        return $this->appConfig;
    }

    /**
     * Get configuration value by key.
     */
    public function getAppConfigValue(string $key, mixed $default = null): mixed
    {
        return $this->appConfig[$key] ?? $default;
    }

    /**
     * Get default route for current application.
     *
     * @return array{module: string, controller: string, action: string}
     */
    public function getDefaultRoute(): array
    {
        return $this->configLoader->getDefaultRoute($this->appConfig);
    }

    /**
     * Get application bootstrap instance.
     */
    public function getAppBootstrap(): ?AppBootstrapInterface
    {
        return $this->appBootstrap;
    }
}
