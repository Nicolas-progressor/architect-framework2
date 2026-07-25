<?php

declare(strict_types=1);

namespace Blueprint\Engine\Integrations\Architect;

use Blueprint\Engine\Blueprint;
use Blueprint\Engine\RuntimeFactory;
use Blueprint\Engine\Contracts\BlueprintInterface;

/**
 * Blueprint Service Provider for Architect Framework
 * 
 * Registers Blueprint as a service in Architect's DI container.
 * No static methods, no singletons - pure DI.
 * 
 * @package Blueprint\Engine\Integrations\Architect
 */
class BlueprintServiceProvider
{
    /**
     * Register Blueprint services in Architect container
     * 
     * @param object $container DI container (Architect\Container)
     * @param array $config Blueprint configuration
     */
    public function register(object $container, array $config): void
    {
        // Register Runtime first
        $container->singleton('blueprint.runtime', function () use ($config) {
            return RuntimeFactory::createWithConfig($config);
        });
        
        // Register Blueprint
        $container->singleton('blueprint', function () use ($config, $container) {
            $runtime = $container->get('blueprint.runtime');
            $blueprint = new Blueprint($config, $container, $runtime);
            
            // Add paths from config
            $paths = $config['paths'] ?? [];
            foreach ($paths as $path) {
                $blueprint->addPath($path);
            }
            
            // Ensure cache directory exists
            if (!empty($config['cache'])) {
                $cacheDir = $config['cache'];
                if (!is_dir($cacheDir)) {
                    @mkdir($cacheDir, 0755, true);
                }
            }
            
            return $blueprint;
        });
    }
}
    
