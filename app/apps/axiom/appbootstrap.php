<?php

declare(strict_types=1);

namespace app\axiom;

use Axiom\Orm\Connection\ConnectionManager;

/**
 * Application bootstrap for axiom app.
 */
class appbootstrap
{
    /**
     * Initialize Axiom ORM on core_init.
     */
    public function method_core_init($container): void
    {
        $environment = $container->get('environment');
        $dbConfig = $environment->get('database');

        if ($dbConfig) {
            $config = [
                'default' => $dbConfig['default'] ?? 'mysql',
                'connections' => $dbConfig['connections'] ?? []
            ];
            ConnectionManager::loadConfig($config, $config['default']);
        }
    }
}
