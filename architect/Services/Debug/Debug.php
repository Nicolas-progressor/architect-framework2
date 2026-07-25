<?php

declare(strict_types=1);

namespace Architect\Services\Debug;

use Architect\Core\Container;
use Architect\Services\Debug\Contracts\DebugCollectorInterface;
use Architect\Services\Debug\Contracts\DebugInterface;
use Architect\Services\Debug\Traits\DebugFormatterTrait;
use Architect\Support\AbstractService;

/**
 * Debug service providing interactive debug panel at the bottom of the screen.
 * Tracks execution time, memory usage, queries, cache stats, sessions, and more.
 */
class Debug extends AbstractService implements DebugInterface
{
    use DebugFormatterTrait;

    private const MAX_MESSAGES = 1000;

    private bool $enabled = false;
    private array $config = [];
    private float $startTime;
    private float $startMemory;
    private array $timers = [];
    private array $queries = [];
    private array $cacheStats = ['hits' => 0, 'misses' => 0, 'operations' => []];
    private array $sessionData = [];
    private array $sessionMeta = [];
    private ?DebugCollectorInterface $collector = null;
    private array $stageTimers = [];
    private array $stageMemory = [];
    private string $currentStage = '';
    private array $blueprintData = [
        'enabled' => false,
        'templates' => [],
        'compilations' => [],
        'errors' => [],
        'cache' => [],
        'loader_paths' => [],
    ];

    public function __construct(Container $container)
    {
        parent::__construct($container);

        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage();
    }

    public function boot(): void
    {
        // Load debug configuration with application-specific override
        $loader = $this->container->get('config.loader');
        $appPath = null;
        if ($this->container->has('apps')) {
            $apps = $this->container->get('apps');
            $appPath = $apps->getAppDir();
        }
        $configService = $loader->loadWithAppOverride('debug', $appPath);
        $this->config = $configService->all();

        $env = $this->container->get('environment');
        $this->enabled = ($this->config['enabled'] ?? false) && !$env->isProduction();

        if ($this->enabled && ($this->config['ip_whitelist'] ?? [])) {
            $this->enabled = $this->checkIpWhitelist();
        }

        if ($this->enabled && ($this->config['collect_custom_data'] ?? true)) {
            $this->collector = new DebugDataCollector();
        }

        if ($this->enabled && ($this->config['show_session'] ?? true)) {
            $this->collectSessionData();
        }

        $this->startStage('initialization');
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function startStage(string $stage): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->currentStage = $stage;
        $this->stageTimers[$stage] = [
            'start' => microtime(true) - $this->startTime,
            'duration' => 0,
        ];
        $this->stageMemory[$stage] = [
            'start' => memory_get_usage(),
            'end' => 0,
            'peak' => 0,
        ];
    }

    public function endStage(): void
    {
        if (!$this->enabled || !$this->currentStage) {
            return;
        }

        $currentMemory = memory_get_usage();

        if (isset($this->stageTimers[$this->currentStage])) {
            $this->stageTimers[$this->currentStage]['duration'] =
                (microtime(true) - $this->startTime) - $this->stageTimers[$this->currentStage]['start'];
        }

        if (isset($this->stageMemory[$this->currentStage])) {
            $this->stageMemory[$this->currentStage]['end'] = $currentMemory;
            $this->stageMemory[$this->currentStage]['peak'] = memory_get_peak_usage();
        }

        $this->currentStage = '';
    }

    public function log(string $message, string $category = 'info', array $context = []): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->queries[] = [
            'time' => microtime(true) - $this->startTime,
            'category' => $category,
            'message' => $message,
            'context' => $context,
            'memory' => memory_get_usage(),
        ];
    }

    public function query(string $query, float $duration = 0, array $params = [], string $source = 'database'): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->queries[] = [
            'time' => microtime(true) - $this->startTime,
            'query' => $query,
            'duration' => $duration,
            'params' => $params,
            'is_slow' => $duration > 0.1,
            'source' => $source,
            'memory' => memory_get_usage(),
        ];
    }

    public function cacheHit(string $key): void
    {
        $this->recordCacheOperation($key, 'get', 'hit');
        $this->cacheStats['hits']++;
    }

    public function cacheMiss(string $key): void
    {
        $this->recordCacheOperation($key, 'get', 'miss');
        $this->cacheStats['misses']++;
    }

    public function cacheSet(string $key): void
    {
        $this->recordCacheOperation($key, 'set');
    }

    private function recordCacheOperation(string $key, string $action, ?string $result = null): void
    {
        if (!$this->enabled) {
            return;
        }

        $operation = [
            'time' => microtime(true) - $this->startTime,
            'key' => $key,
            'action' => $action,
        ];

        if ($result !== null) {
            $operation['result'] = $result;
        }

        $this->cacheStats['operations'][] = $operation;
    }

    public function setSessionData(array $session): void
    {
        $this->sessionData = $session;
    }

    public function getLogs(): array
    {
        return array_filter($this->queries, fn($q) => !isset($q['query']));
    }

    public function getQueries(): array
    {
        return array_filter($this->queries, fn($q) => isset($q['query']));
    }

    public function getCacheStats(): array
    {
        return $this->cacheStats;
    }

    public function getSessionData(): array
    {
        return $this->sessionData;
    }

    public function getSessionMeta(): array
    {
        return $this->sessionMeta;
    }

    public function getStageTimers(): array
    {
        return $this->stageTimers;
    }

    public function clear(): void
    {
        $this->queries = [];
        $this->cacheStats = ['hits' => 0, 'misses' => 0, 'operations' => []];
        $this->stageTimers = [];
        $this->stageMemory = [];

        if ($this->collector !== null) {
            $this->collector->clear();
        }
    }

    public function getData(): array
    {
        $totalTime = microtime(true) - $this->startTime;
        $memoryPeak = memory_get_peak_usage();
        $memoryLimit = $this->parseMemoryLimit((string) ini_get('memory_limit'));

        $errorCount = 0;
        $warningCount = 0;
        foreach ($this->getLogs() as $log) {
            $category = $log['category'] ?? '';
            if ($category === 'error') {
                $errorCount++;
            } elseif ($category === 'warning') {
                $warningCount++;
            }
        }

        $hasSlowQueries = false;
        foreach ($this->getQueries() as $query) {
            if (($query['duration'] ?? 0) > 0.1) {
                $hasSlowQueries = true;
                break;
            }
        }

        $totalCacheOps = $this->cacheStats['hits'] + $this->cacheStats['misses'];
        $hitRatio = $totalCacheOps > 0
            ? round(($this->cacheStats['hits'] / $totalCacheOps) * 100)
            : 0;

        // Cache configuration data
        $cacheConfigData = [];
        try {
            if ($this->container->has('cache.config')) {
                $config = $this->container->get('cache.config');
                $cacheConfigData = [
                    'default_store' => $config->getDefaultStore(),
                    'prefix' => $config->getPrefix(),
                    'stores' => $config->getStoreNames(),
                ];
            }
        } catch (\Exception $e) {
            // ignore
        }

        $env = $this->container->get('environment');

        $collectorData = $this->collector !== null ? $this->collector->getData() : null;

        // Performance monitoring data
        $performanceData = [];
        if ($this->container->has('performance.monitor')) {
            try {
                $performanceMonitor = $this->container->get('performance.monitor');
                $performanceData = $performanceMonitor->getMetrics();
            } catch (\Exception $e) {
                // ignore
            }
        }

        // Profiler data
        $profilerData = [];
        if ($this->container->has('performance.profiler')) {
            try {
                $profiler = $this->container->get('performance.profiler');
                $profilerData = $profiler->createReport();
            } catch (\Exception $e) {
                // ignore
            }
        }

        return [
            'total_time' => $totalTime,
            'time_color' => $this->getTimeColor($totalTime),
            'stages' => $this->stageTimers,
            'stage_memory' => $this->stageMemory,
            'memory_peak' => $memoryPeak,
            'memory_limit' => $memoryLimit,
            'memory_percent' => $memoryLimit > 0 ? round(($memoryPeak / $memoryLimit) * 100) : 0,
            'memory_color' => $this->getMemoryColor($memoryPeak, $memoryLimit),
            'queries' => $this->getQueries(),
            'query_count' => count($this->getQueries()),
            'has_slow_queries' => $hasSlowQueries,
            'logs' => $this->getLogs(),
            'error_count' => $errorCount,
            'warning_count' => $warningCount,
            'cache_hits' => $this->cacheStats['hits'],
            'cache_misses' => $this->cacheStats['misses'],
            'cache_hit_ratio' => $hitRatio,
            'cache_color' => $this->getCacheColor($hitRatio),
            'cache_operations' => $this->cacheStats['operations'],
            'cache_operations_count' => count($this->cacheStats['operations']),
            'cache_config' => $cacheConfigData,
            'session_count' => count($this->sessionData),
            'session_data' => $this->sessionData,
            'session_meta' => $this->sessionMeta,
            'environment' => $env->getEnvironment(),
            'env_color' => $this->getEnvColor($env->getEnvironment()),
            'collector' => $collectorData,
            'has_custom_data' => $collectorData !== null && $collectorData['has_data'],
            'routing' => $this->getRoutingData(),
            'blueprint' => $this->blueprintData,
            'has_blueprint' => $this->blueprintData['enabled'],
            'system_logs' => $this->getSystemLogs(),
            'performance' => $performanceData,
            'profiler' => $profilerData,
            'has_performance' => !empty($performanceData) || !empty($profilerData),
        ];
    }

    public function blueprintCompile(string $template, string $compiledPath, bool $fromCache): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->blueprintData['templates'][] = [
            'name' => $template,
            'compiled_path' => $compiledPath,
            'from_cache' => $fromCache,
            'time' => microtime(true) - $this->startTime,
        ];
    }

    public function blueprintError(string $template, string $message, ?string $compiledCode = null): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->blueprintData['errors'][] = [
            'template' => $template,
            'message' => $message,
            'compiled_code' => $compiledCode,
            'time' => microtime(true) - $this->startTime,
        ];
    }

    public function setBlueprintData(array $data): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->blueprintData = array_merge($this->blueprintData, $data);
    }

    public function getBlueprintData(): array
    {
        return $this->blueprintData;
    }

    public function isBlueprintEnabled(): bool
    {
        return $this->blueprintData['enabled'];
    }

    public function getCollector(): ?DebugCollectorInterface
    {
        return $this->collector;
    }

    public function render(): void
    {
        if (!$this->enabled) {
            return;
        }

        $data = $this->getData();
        include_once __DIR__ . '/View/Panel.php';
    }

    public function renderWidget(): string
    {
        if (!$this->enabled) {
            return '';
        }

        $data = $this->getData();

        ob_start();
        require_once __DIR__ . '/View/Widget.php';
        return ob_get_clean();
    }

    private function collectSessionData(): void
    {
        $status = session_status();
        $sessionActive = $status === PHP_SESSION_ACTIVE;

        $cookieParams = $sessionActive ? session_get_cookie_params() : [];
        $lifetime = $cookieParams['lifetime'] ?? 0;
        $expires = $lifetime > 0 ? time() + $lifetime : null;

        $sessionSize = 0;
        $sessionKeys = [];
        $sensitiveKeys = [];

        if ($sessionActive && !empty($_SESSION)) {
            $this->sessionData = $_SESSION;
            $sessionSize = strlen(serialize($_SESSION));
            $sessionKeys = array_keys($_SESSION);

            // Detect sensitive keys
            $sensitivePatterns = ['/password/i', '/token/i', '/secret/i', '/key/i', '/auth/i', '/credential/i'];
            foreach ($sessionKeys as $key) {
                foreach ($sensitivePatterns as $pattern) {
                    if (preg_match($pattern, $key)) {
                        $sensitiveKeys[] = $key;
                        break;
                    }
                }
            }
        } else {
            $this->sessionData = [];
        }

        $this->sessionMeta = [
            'status' => match ($status) {
                PHP_SESSION_ACTIVE => 'active',
                PHP_SESSION_NONE => 'none',
                PHP_SESSION_DISABLED => 'disabled',
                default => 'unknown',
            },
            'id' => $sessionActive ? session_id() : '',
            'name' => $sessionActive ? session_name() : '',
            'cookie_params' => $cookieParams,
            'cookie_value' => $_COOKIE[session_name()] ?? null,
            'created' => $sessionActive ? ($_SESSION['__created'] ?? null) : null,
            'last_activity' => $sessionActive ? ($_SESSION['__last_activity'] ?? null) : null,
            'lifetime' => $lifetime,
            'expires' => $expires,
            'size_bytes' => $sessionSize,
            'size_human' => $this->formatBytes($sessionSize),
            'keys_count' => count($sessionKeys),
            'sensitive_keys' => array_unique($sensitiveKeys),
            'has_sensitive_data' => !empty($sensitiveKeys),
        ];
    }

    private function checkIpWhitelist(): bool
    {
        $whitelist = $this->config['ip_whitelist'] ?? [];
        if (empty($whitelist)) {
            return true;
        }

        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
        return in_array($clientIp, $whitelist, true);
    }

    private function getTimeColor(float $time): string
    {
        $ms = $time * 1000;
        if ($ms < 100) {
            return 'green';
        } elseif ($ms < 500) {
            return 'yellow';
        }
        return 'red';
    }

    private function getMemoryColor(int $used, int $limit): string
    {
        if ($limit === 0) {
            return 'green';
        }

        $percent = ($used / $limit) * 100;
        if ($percent < 50) {
            return 'green';
        } elseif ($percent < 80) {
            return 'yellow';
        }
        return 'red';
    }

    private function getCacheColor(float $hitRatio): string
    {
        if ($hitRatio >= 70) {
            return 'green';
        } elseif ($hitRatio >= 40) {
            return 'yellow';
        }
        return 'red';
    }

    private function getEnvColor(string $env): string
    {
        return match ($env) {
            'development' => 'yellow',
            'testing' => 'green',
            'staging' => 'orange',
            'production' => 'red',
            default => 'gray',
        };
    }

    private function getRoutingData(): array
    {
        try {
            $router = $this->container->get('router');

            $currentRoute = [
                'path' => $router->path ?? '/',
                'module' => $router->getModule() ?? '',
                'controller' => $router->getController() ?? '',
                'action' => $router->getAction() ?? '',
                'segments' => $router->segments ?? [],
                'params' => $router->params ?? [],
            ];

            // Try to get routes from router first
            $routes = $router->routes ?? [];

            // If routes are empty, load them manually
            if (empty($routes)) {
                $routes = $this->loadAllRoutes();
            }

            $routeFiles = [];
            $globalRoutesDir = APP_DIR . 'routes/';
            if (is_dir($globalRoutesDir)) {
                foreach (glob($globalRoutesDir . '*.json') as $file) {
                    $content = file_get_contents($file);
                    $data = json_decode($content, true);
                    $routeFiles[] = [
                        'path' => $file,
                        'type' => 'global',
                        'name' => basename($file),
                        'content' => $data,
                    ];

                    // Also add routes from this file
                    if (isset($data['routes'])) {
                        $routes = array_merge($routes, $data['routes']);
                    }
                }
            }

            // Load app routes
            $apps = $this->container->get('apps');
            $appDir = $apps->getAppDir();
            $appRoutesDir = $appDir . 'routes/';
            if (is_dir($appRoutesDir)) {
                foreach (glob($appRoutesDir . '*.json') as $file) {
                    $content = file_get_contents($file);
                    $data = json_decode($content, true);
                    $routeFiles[] = [
                        'path' => $file,
                        'type' => 'app',
                        'name' => basename($file),
                        'content' => $data,
                    ];

                    if (isset($data['routes'])) {
                        $routes = array_merge($routes, $data['routes']);
                    }
                }
            }

            // Load config routes
            $configRoutesFile = $appDir . 'config/routes.json';
            if (file_exists($configRoutesFile)) {
                $content = file_get_contents($configRoutesFile);
                $data = json_decode($content, true);
                $routeFiles[] = [
                    'path' => $configRoutesFile,
                    'type' => 'config',
                    'name' => 'routes.json',
                    'content' => $data,
                ];

                if (isset($data['routes'])) {
                    $routes = array_merge($routes, $data['routes']);
                }
            }

            return [
                'current' => $currentRoute,
                'routes' => $routes,
                'route_files' => $routeFiles,
            ];
        } catch (\Exception $e) {
            return [
                'current' => [],
                'routes' => [],
                'route_files' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Load all routes from all sources.
     */
    private function loadAllRoutes(): array
    {
        $routes = [];

        // Global routes
        $globalRoutesDir = APP_DIR . 'routes/';
        if (is_dir($globalRoutesDir)) {
            foreach (glob($globalRoutesDir . '*.json') as $file) {
                $content = file_get_contents($file);
                $data = json_decode($content, true);
                if (isset($data['routes'])) {
                    $routes = array_merge($routes, $data['routes']);
                }
            }
        }

        // App routes
        try {
            $apps = $this->container->get('apps');
            $appDir = $apps->getAppDir();

            // App routes directory
            $appRoutesDir = $appDir . 'routes/';
            if (is_dir($appRoutesDir)) {
                foreach (glob($appRoutesDir . '*.json') as $file) {
                    $content = file_get_contents($file);
                    $data = json_decode($content, true);
                    if (isset($data['routes'])) {
                        $routes = array_merge($routes, $data['routes']);
                    }
                }
            }

            // Config routes
            $configRoutesFile = $appDir . 'config/routes.json';
            if (file_exists($configRoutesFile)) {
                $content = file_get_contents($configRoutesFile);
                $data = json_decode($content, true);
                if (isset($data['routes'])) {
                    $routes = array_merge($routes, $data['routes']);
                }
            }
        } catch (\Exception $e) {
            // Apps service not available
        }

        return $routes;
    }

    /**
     * Get system logs from log files.
     */
    private function getSystemLogs(): array
    {
        if (!$this->enabled) {
            return [];
        }

        $logs = [];
        $logDir = defined('APP_DIR') ? APP_DIR . 'logs/' : dirname(__DIR__, 3) . '/app/logs/';
        if (!is_dir($logDir)) {
            return [];
        }

        // Read today's system log file
        $today = date('Y-m-d');
        $systemFile = $logDir . 'system_' . $today . '.log';
        if (!file_exists($systemFile)) {
            // Fallback to the most recent system log file
            $files = glob($logDir . 'system_*.log');
            if (empty($files)) {
                return [];
            }
            $systemFile = end($files);
        }

        $lines = file($systemFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return [];
        }

        // Take last 20 lines
        $recentLines = array_slice($lines, -20);
        foreach ($recentLines as $line) {
            // Parse line format: [Y-m-d H:i:s] channel.LEVEL: message
            if (preg_match('/^\[([^\]]+)\] (\w+)\.(\w+): (.+)$/', $line, $matches)) {
                $time = strtotime($matches[1]);
                $channel = $matches[2];
                $level = strtolower($matches[3]);
                $message = $matches[4];
            } else {
                // Fallback: treat whole line as message
                $time = time();
                $channel = 'system';
                $level = 'info';
                $message = $line;
            }

            $logs[] = [
                'time' => $time,
                'channel' => $channel,
                'level' => $level,
                'message' => $message,
                'source' => 'system',
            ];
        }

        return $logs;
    }
}
