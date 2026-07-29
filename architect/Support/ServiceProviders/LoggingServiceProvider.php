<?php

declare(strict_types=1);

namespace Architect\Support\ServiceProviders;

use Architect\Contracts\Core\ContainerInterface;
use Architect\Services\Debug\Debug;
use Architect\Support\AbstractServiceProvider;

/**
 * Logging service provider: logger, debug, integration.
 */
class LoggingServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register(ContainerInterface $container): void
    {
        // Logger service
        $this->registerFactory($container, 'logger', function ($c) {
            $configService = $c->get('config.logger');
            $configArray = $configService->all();

            // If log_dir is null, use default
            if (!isset($configArray['log_dir']) || $configArray['log_dir'] === null) {
                $configArray['log_dir'] = defined('APP_DIR')
                    ? APP_DIR . 'logs/'
                    : dirname(__DIR__, 2) . '/app/logs/';
            }

            $config = \Architect\Services\Logger\LoggerConfig::fromArray($configArray);
            return new \Architect\Services\Logger\Logger($c, $config);
        });

        // Debug service
        $this->registerFactory($container, 'debug', fn($c) => new Debug($c));

        // Debug collector
        $this->registerFactory($container, 'debug.collector', function ($c) {
            $debug = $c->get('debug');
            return $debug->getCollector();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $container): void
    {
        // Boot debug service to load configuration
        $debug = $container->get('debug');
        $debug->boot();

        // Setup Logger -> Debug integration
        $this->setupLoggerDebugIntegration($container);

        // Setup debug collector
        if ($debug->isEnabled()) {
            $collector = $debug->getCollector();
            if ($collector) {
                \Architect\Support\Debug::setCollector($collector);
            }
        }
    }

    /**
     * Setup Logger -> Debug integration via callback.
     */
    private function setupLoggerDebugIntegration(ContainerInterface $container): void
    {
        $logger = $container->get('logger');
        $debug = $container->get('debug');

        // Only set callback if debug is enabled
        if ($debug->isEnabled() && method_exists($logger, 'setDebugCallback')) {
            $logger->setDebugCallback(function (array $entry) use ($debug) {
                $category = $this->mapLogLevelToCategory($entry['level']);
                $debug->log($entry['message'], $category, [
                    'channel' => $entry['channel'],
                    'level' => $entry['level'],
                    'time' => $entry['time'],
                ]);
            });
        }
    }

    /**
     * Map PSR-3 log level to debug category.
     */
    private function mapLogLevelToCategory(string $level): string
    {
        return match ($level) {
            'emergency', 'alert', 'critical', 'error' => 'error',
            'warning' => 'warning',
            'debug' => 'debug',
            default => 'info',
        };
    }
}
