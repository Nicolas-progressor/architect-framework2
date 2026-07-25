<?php

declare(strict_types=1);

namespace Architect\Services\Cache;

use Architect\Core\Contracts\ContainerInterface;
use Architect\Services\Blueprint\BlueprintService;
use Axiom\Cache\CacheManager as AxiomCacheManager;
use Blueprint\Engine\Blueprint;
use Blueprint\Engine\Cache\CacheManager as BlueprintCacheManager;

/**
 * Cache Orchestrator
 * 
 * Provides unified management of cache across Architect, Blueprint, and Axiom.
 * Allows clearing cache, getting statistics, and controlling cache drivers
 * from a single interface.
 */
class CacheOrchestrator
{
    private ContainerInterface $container;
    private ?BlueprintService $blueprintService = null;
    private bool $axiomAvailable = false;
    private bool $blueprintAvailable = false;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->detectComponents();
    }

    /**
     * Detect availability of Axiom and Blueprint
     */
    private function detectComponents(): void
    {
        $this->axiomAvailable = class_exists(AxiomCacheManager::class);
        $this->blueprintAvailable = class_exists(BlueprintService::class);
    }

    /**
     * Check if Axiom cache is available
     */
    public function isAxiomAvailable(): bool
    {
        return $this->axiomAvailable;
    }

    /**
     * Check if Blueprint cache is available
     */
    public function isBlueprintAvailable(): bool
    {
        return $this->blueprintAvailable;
    }

    /**
     * Clear all caches (Architect, Blueprint, Axiom)
     */
    public function clearAll(): array
    {
        $results = [];

        // Clear Architect cache
        $results['architect'] = $this->clearArchitectCache();

        // Clear Blueprint cache if available
        if ($this->blueprintAvailable) {
            $results['blueprint'] = $this->clearBlueprintCache();
        }

        // Clear Axiom cache if available
        if ($this->axiomAvailable) {
            $results['axiom'] = $this->clearAxiomCache();
        }

        return $results;
    }

    /**
     * Clear only Architect cache
     */
    public function clearArchitectCache(): bool
    {
        try {
            $manager = $this->container->get('cache');
            if ($manager instanceof CacheManager) {
                $manager->store()->clear();
                return true;
            }
        } catch (\Throwable $e) {
            // Log error if needed
        }
        return false;
    }

    /**
     * Clear Blueprint template cache
     */
    public function clearBlueprintCache(): bool
    {
        if (!$this->blueprintAvailable) {
            return false;
        }

        try {
            $blueprintService = $this->container->get('blueprint');
            if ($blueprintService instanceof BlueprintService) {
                return $blueprintService->clearCache();
            }
        } catch (\Throwable $e) {
            // Log error
        }
        return false;
    }

    /**
     * Clear Axiom query cache
     */
    public function clearAxiomCache(): bool
    {
        if (!$this->axiomAvailable) {
            return false;
        }

        try {
            AxiomCacheManager::flush();
            return true;
        } catch (\Throwable $e) {
            // Log error
        }
        return false;
    }

    /**
     * Get cache statistics from all systems
     */
    public function getStats(): array
    {
        $stats = [];

        // Architect stats
        $stats['architect'] = $this->getArchitectStats();

        // Blueprint stats
        if ($this->blueprintAvailable) {
            $stats['blueprint'] = $this->getBlueprintStats();
        }

        // Axiom stats
        if ($this->axiomAvailable) {
            $stats['axiom'] = $this->getAxiomStats();
        }

        return $stats;
    }

    /**
     * Get Architect cache statistics
     */
    private function getArchitectStats(): array
    {
        try {
            $manager = $this->container->get('cache');
            if ($manager instanceof CacheManager) {
                $store = $manager->store();
                $driver = $manager->getDefaultDriver();
                $config = $manager->getConfig();
                return [
                    'driver' => $driver,
                    'default_store' => $config->getDefaultStore(),
                    'prefix' => $config->getPrefix(),
                    'stores' => array_keys($config->getStores()),
                ];
            }
        } catch (\Throwable $e) {
            // ignore
        }
        return ['driver' => 'unknown', 'error' => 'unavailable'];
    }

    /**
     * Get Blueprint cache statistics
     */
    private function getBlueprintStats(): array
    {
        if (!$this->blueprintAvailable) {
            return ['error' => 'blueprint not available'];
        }

        try {
            $blueprintService = $this->container->get('blueprint');
            if (!$blueprintService instanceof BlueprintService) {
                return ['error' => 'blueprint service not found'];
            }
            $blueprint = $blueprintService->getBlueprint();
            if (!$blueprint instanceof Blueprint) {
                return ['error' => 'blueprint instance not found'];
            }
            $loader = $blueprint->getLoader();
            $cacheManager = $loader->getCacheManager();
            if ($cacheManager instanceof BlueprintCacheManager) {
                return $cacheManager->getStats();
            }
        } catch (\Throwable $e) {
            // Log error
        }
        return ['error' => 'failed to retrieve stats'];
    }

    /**
     * Get Axiom cache statistics
     */
    private function getAxiomStats(): array
    {
        if (!$this->axiomAvailable) {
            return ['error' => 'axiom not available'];
        }

        try {
            $enabled = AxiomCacheManager::isEnabled();
            $driver = AxiomCacheManager::getDriverName();
            $size = 0; // Not available
            return [
                'enabled' => $enabled,
                'driver' => $driver,
                'size' => $size,
            ];
        } catch (\Throwable $e) {
            // ignore
        }
        return ['enabled' => false];
    }

    /**
     * Enable or disable cache for a specific system
     */
    public function setCacheEnabled(string $system, bool $enabled): bool
    {
        switch ($system) {
            case 'axiom':
                if ($this->axiomAvailable) {
                    if ($enabled) {
                        AxiomCacheManager::enable();
                    } else {
                        AxiomCacheManager::disable();
                    }
                    return true;
                }
                break;
            case 'blueprint':
                if ($this->blueprintAvailable) {
                    try {
                        $blueprintService = $this->container->get('blueprint');
                        if ($blueprintService instanceof BlueprintService) {
                            $blueprint = $blueprintService->getBlueprint();
                            $loader = $blueprint->getLoader();
                            $loader->setCacheEnabled($enabled);
                            return true;
                        }
                    } catch (\Throwable $e) {
                        // Log error
                    }
                }
                break;
            case 'architect':
                // Architect cache can be enabled/disabled via configuration
                // Not implemented yet
                break;
        }
        return false;
    }

    /**
     * Check if cache is enabled for a system
     */
    public function isCacheEnabled(string $system): ?bool
    {
        switch ($system) {
            case 'axiom':
                if ($this->axiomAvailable) {
                    return AxiomCacheManager::isEnabled();
                }
                break;
            case 'blueprint':
                if ($this->blueprintAvailable) {
                    try {
                        $blueprintService = $this->container->get('blueprint');
                        if ($blueprintService instanceof BlueprintService) {
                            $blueprint = $blueprintService->getBlueprint();
                            $loader = $blueprint->getLoader();
                            return $loader->isCacheEnabled();
                        }
                    } catch (\Throwable $e) {
                        // Log error
                    }
                }
                break;
            case 'architect':
                // Architect cache is always enabled if configured
                return true;
        }
        return null;
    }
}