<?php

declare(strict_types=1);

namespace Architect\Services\App;

use Architect\Core\Contracts\StatementInterface;
use Architect\Services\App\Contracts\AppBootstrapInterface;
use Psr\Log\LoggerInterface;

/**
 * Loader for application bootstrap classes.
 * 
 * Handles loading bootstrap files and registering statement handlers.
 */
class AppBootstrapLoader
{
    /**
     * Supported statement names.
     */
    private const STATEMENTS = [
        'core_preinit',
        'core_init',
        'core_load',
        'core_post_load',
        'app_load',
        'app_data',
        'app_output',
        'render',
    ];

    /**
     * Create bootstrap loader.
     */
    public function __construct(
        private readonly StatementInterface $statement,
        private readonly ?LoggerInterface $logger = null
    ) {}

    /**
     * Load bootstrap for application.
     */
    public function load(string $appDir, string $appName): ?AppBootstrapInterface
    {
        $bootstrapFile = $appDir . 'appbootstrap.php';

        if (!file_exists($bootstrapFile)) {
            return null;
        }

        require_once $bootstrapFile;

        $className = '\\app\\' . strtolower($appName) . '\\appbootstrap';

        if (!class_exists($className)) {
            $this->logger?->warning('AppBootstrap file exists but class not found', [
                'file' => $bootstrapFile,
                'class' => $className,
            ]);
            return null;
        }

        $instance = new $className();

        if (!$instance instanceof AppBootstrapInterface) {
            // Support legacy bootstraps without interface
            $this->registerStatements($instance);
            return null;
        }

        $this->registerStatements($instance);
        
        return $instance;
    }

    /**
     * Register statement handlers from bootstrap.
     */
    private function registerStatements(object $bootstrap): void
    {
        $methods = get_class_methods($bootstrap);

        foreach (self::STATEMENTS as $statementName) {
            $method = 'method_' . $statementName;
            
            if (in_array($method, $methods, true)) {
                $this->statement->on(
                    $statementName,
                    fn() => $bootstrap->{$method}(),
                    10
                );
            }
        }
    }

    /**
     * Get supported statement names.
     * 
     * @return array<string>
     */
    public function getSupportedStatements(): array
    {
        return self::STATEMENTS;
    }
}
