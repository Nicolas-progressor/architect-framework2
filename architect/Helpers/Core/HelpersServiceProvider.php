<?php

declare(strict_types=1);

namespace Architect\Helpers\Core;

use Architect\Contracts\Core\ContainerInterface;
use Architect\Support\AbstractServiceProvider;

/**
 * Service provider for Helpers system.
 */
class HelpersServiceProvider extends AbstractServiceProvider
{
    /**
     * Register services.
     */
    public function register(ContainerInterface $container): void
    {
        // Register container itself as a service for ContainerInterface
        if (!$container->has(ContainerInterface::class)) {
            $container->set(ContainerInterface::class, $container);
        }

        // Create HelperManager and register it
        $manager = new HelperManager($container);
        $container->set('helpers', $manager);

        // Load helpers configuration
        $helpersConfig = [];
        if ($container->has('config.helpers')) {
            $config = $container->get('config.helpers');
            // If config is an object with a 'get' method, try to get 'helpers' key
            if (is_object($config) && method_exists($config, 'get')) {
                $helpersConfig = $config->get('helpers', []);
            } elseif (is_array($config)) {
                $helpersConfig = $config;
            }
        } elseif ($container->has('config')) {
            $config = $container->get('config');
            $helpersConfig = $config->get('helpers', []);
        }

        // Register helpers from config (if any)
        if (!empty($helpersConfig) && is_array($helpersConfig)) {
            $manager->registerMany($helpersConfig);
        }

        // Register default helpers via HelperDiscovery (fast, no scanning)
        $discovery = new HelperDiscovery();
        $defaultHelpers = $discovery->discover();
        foreach ($defaultHelpers as $alias => $class) {
            if (!$manager->has($alias)) {
                $manager->register($alias, $class);
            }
        }

        // Set container for Facade
        Facade::setContainer($container);

        // Register class aliases for facades (so Helper_Html works without namespace)
        $this->registerFacadeAliases();
    }

    /**
     * Register class aliases for facades (so Helper_Html works without namespace).
     */
    private function registerFacadeAliases(): void
    {
        // List of known facade classes (can be extended via configuration in the future)
        $defaultFacades = [
            'Helper_Html' => 'Architect\Helpers\Html\Facades\Helper_Html',
            'Helper_Assets' => 'Architect\Helpers\Assets\Facades\Helper_Assets',
            'Helper_Breadcrumbs' => 'Architect\Helpers\Breadcrumbs\Facades\Helper_Breadcrumbs',
            'Helper_Title' => 'Architect\Helpers\Title\Facades\Helper_Title',
            'Helper_Request' => 'Architect\Helpers\Request\Facades\Helper_Request',
            'Helper_Db' => 'Architect\Helpers\Db\Facades\Helper_Db',
            'Helper_Arr' => 'Architect\Helpers\ArrayHelper\Facades\Helper_Arr',
            'Helper_Number' => 'Architect\Helpers\NumberHelper\Facades\Helper_Number',
        ];

        foreach ($defaultFacades as $alias => $facadeClass) {
            if (class_exists($facadeClass) && !class_exists($alias, false)) {
                class_alias($facadeClass, $alias);
            }
        }
    }

    /**
     * Boot services.
     */
    public function boot(ContainerInterface $container): void
    {
        // Nothing to boot
    }
}
