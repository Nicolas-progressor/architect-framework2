<?php

namespace Architect\Services\Performance\Providers;

use Architect\Contracts\ServiceProviderInterface;
use Architect\Core\Contracts\ContainerInterface;
use Architect\Services\Performance\Alerts\AlertManager;
use Architect\Services\Performance\Contracts\AlertManagerInterface;
use Architect\Services\Performance\Contracts\MetricAggregatorInterface;
use Architect\Services\Performance\Contracts\MetricCollectorInterface;
use Architect\Services\Performance\Contracts\MetricStorageInterface;
use Architect\Services\Performance\Contracts\PerformanceMonitorInterface;
use Architect\Services\Performance\Export\ExportManager;
use Architect\Services\Performance\Metrics\MetricAggregator;
use Architect\Services\Performance\Metrics\MetricCollector;
use Architect\Services\Performance\PerformanceMonitor;
use Architect\Services\Performance\Storage\SessionStorage;

class PerformanceServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerInterface $container): void
    {
        // Register storage
        $container->singleton(MetricStorageInterface::class, function ($c) {
            return new SessionStorage();
        });

        // Register metric collector
        $container->singleton(MetricCollectorInterface::class, function ($c) {
            return new MetricCollector($c->get(MetricStorageInterface::class));
        });

        // Register metric aggregator
        $container->singleton(MetricAggregatorInterface::class, function ($c) {
            return new MetricAggregator($c->get(MetricStorageInterface::class));
        });

        // Register export manager
        $container->singleton(ExportManager::class, function ($c) {
            return new ExportManager();
        });

        // Register alert manager
        $container->singleton(AlertManagerInterface::class, function ($c) {
            return new AlertManager(
                $c->get(PerformanceMonitorInterface::class),
                $c->get(MetricStorageInterface::class)
            );
        });

        // Register performance monitor
        $container->singleton(PerformanceMonitorInterface::class, function ($c) {
            return new PerformanceMonitor(
                $c->get(MetricCollectorInterface::class),
                $c->get(MetricAggregatorInterface::class),
                $c->get(MetricStorageInterface::class),
                $c->get(ExportManager::class)
            );
        });

        // Register profiler
        $container->singleton('performance.profiler', function ($c) {
            return new \Architect\Services\Performance\Profiler();
        });

        // Register alias for convenience
        $container->alias('performance', PerformanceMonitorInterface::class);
        $container->alias('performance.monitor', PerformanceMonitorInterface::class);
        $container->alias('performance.collector', MetricCollectorInterface::class);
        $container->alias('performance.alerts', AlertManagerInterface::class);
        $container->alias('profiler', 'performance.profiler');
    }

    public function boot(ContainerInterface $container): void
    {
        // Load configuration
        $config = $this->loadPerformanceConfig();

        // Configure alert thresholds from config
        if ($container->has(AlertManagerInterface::class)) {
            $alertManager = $container->get(AlertManagerInterface::class);

            if (isset($config['thresholds'])) {
                foreach ($config['thresholds'] as $metric => $threshold) {
                    $alertManager->setThreshold($metric, $threshold);
                }
            }
        }

        // Start performance monitoring if enabled
        if (isset($config['alerts']['enabled']) && $config['alerts']['enabled']) {
            $this->initializePerformanceMonitoring($container);
        }
    }

    private function loadPerformanceConfig(): array
    {
        $configPath = __DIR__ . '/../../../app/config/performance.json';

        if (file_exists($configPath)) {
            $content = file_get_contents($configPath);
            return json_decode($content, true) ?? [];
        }

        return [];
    }

    private function initializePerformanceMonitoring(ContainerInterface $container): void
    {
        // In a real implementation, this would set up event listeners
        // and start the monitoring process
    }
}
