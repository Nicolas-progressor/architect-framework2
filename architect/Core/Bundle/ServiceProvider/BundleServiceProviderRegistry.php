<?php

declare(strict_types=1);

namespace Architect\Core\Bundle\ServiceProvider;

use Architect\Contracts\BundleInterface;
use Architect\Contracts\Core\ContainerInterface;
use Architect\Contracts\ServiceProviderInterface;

/**
 * Registers service providers from bundles.
 */
class BundleServiceProviderRegistry
{
    /**
     * Register service providers from a bundle.
     *
     * @param BundleInterface $bundle
     * @param ContainerInterface $container
     */
    public function register(BundleInterface $bundle, ContainerInterface $container): void
    {
        $serviceProviders = $this->discoverServiceProviders($bundle);

        foreach ($serviceProviders as $providerClass) {
            if (class_exists($providerClass)) {
                $provider = new $providerClass();
                if ($provider instanceof ServiceProviderInterface) {
                    $provider->register($container);

                    // Store provider for later booting
                    $this->storeProvider($bundle, $provider, $container);
                }
            }
        }
    }

    /**
     * Discover service providers in a bundle.
     *
     * @param BundleInterface $bundle
     * @return string[]
     */
    private function discoverServiceProviders(BundleInterface $bundle): array
    {
        $reflection = new \ReflectionClass($bundle);
        $bundleDir = dirname($reflection->getFileName());

        $providers = [];

        // Check for ServiceProvider directory
        $providerDir = $bundleDir . '/ServiceProvider';
        if (is_dir($providerDir)) {
            $providers = array_merge($providers, $this->scanDirectoryForProviders($providerDir));
        }

        // Check for Providers directory
        $providerDir = $bundleDir . '/Providers';
        if (is_dir($providerDir)) {
            $providers = array_merge($providers, $this->scanDirectoryForProviders($providerDir));
        }

        // Check for ServiceProviders directory
        $providerDir = $bundleDir . '/ServiceProviders';
        if (is_dir($providerDir)) {
            $providers = array_merge($providers, $this->scanDirectoryForProviders($providerDir));
        }

        // Check for Resources/config/providers.php
        $providersFile = $bundleDir . '/Resources/config/providers.php';
        if (file_exists($providersFile)) {
            $providers = array_merge($providers, $this->loadProvidersFromFile($providersFile));
        }

        // Check for config/providers.php
        $providersFile = $bundleDir . '/config/providers.php';
        if (file_exists($providersFile)) {
            $providers = array_merge($providers, $this->loadProvidersFromFile($providersFile));
        }

        return array_unique($providers);
    }

    /**
     * Scan directory for service provider classes.
     *
     * @param string $directory
     * @return string[]
     */
    private function scanDirectoryForProviders(string $directory): array
    {
        $providers = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $className = $this->getClassNameFromFile($file->getPathname());
                if ($className && $this->isServiceProviderClass($className)) {
                    $providers[] = $className;
                }
            }
        }

        return $providers;
    }

    /**
     * Load providers from a PHP file.
     *
     * @param string $filePath
     * @return string[]
     */
    private function loadProvidersFromFile(string $filePath): array
    {
        $providers = require $filePath;
        if (!is_array($providers)) {
            return [];
        }

        return array_filter($providers, 'is_string');
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
     * Check if a class is a service provider class.
     *
     * @param string $className
     * @return bool
     */
    private function isServiceProviderClass(string $className): bool
    {
        if (!class_exists($className)) {
            return false;
        }

        $reflection = new \ReflectionClass($className);

        // Check if class implements ServiceProviderInterface
        if ($reflection->implementsInterface('Architect\Contracts\ServiceProviderInterface')) {
            return true;
        }

        // Check if class name ends with "ServiceProvider"
        if (str_ends_with($className, 'ServiceProvider')) {
            return true;
        }

        // Check if class extends AbstractServiceProvider
        if ($reflection->isSubclassOf('Architect\Support\AbstractServiceProvider')) {
            return true;
        }

        return false;
    }

    /**
     * Store provider for later booting.
     *
     * @param BundleInterface $bundle
     * @param ServiceProviderInterface $provider
     * @param ContainerInterface $container
     */
    private function storeProvider(BundleInterface $bundle, ServiceProviderInterface $provider, ContainerInterface $container): void
    {
        $bundleName = $bundle->getName();
        $providerKey = 'bundle.' . $bundleName . '.providers';

        $providers = [];
        if ($container->has($providerKey)) {
            $providers = $container->get($providerKey);
        }

        $providers[] = $provider;
        $container->set($providerKey, $providers);
    }

    /**
     * Boot service providers for a bundle.
     *
     * @param BundleInterface $bundle
     * @param ContainerInterface $container
     */
    public function boot(BundleInterface $bundle, ContainerInterface $container): void
    {
        $bundleName = $bundle->getName();
        $providerKey = 'bundle.' . $bundleName . '.providers';

        if (!$container->has($providerKey)) {
            return;
        }

        $providers = $container->get($providerKey);
        foreach ($providers as $provider) {
            if ($provider instanceof ServiceProviderInterface) {
                $provider->boot($container);
            }
        }
    }

    /**
     * Register service providers from all bundles.
     *
     * @param array $bundles
     * @param ContainerInterface $container
     */
    public function registerAll(array $bundles, ContainerInterface $container): void
    {
        foreach ($bundles as $bundle) {
            $this->register($bundle, $container);
        }
    }

    /**
     * Boot service providers for all bundles.
     *
     * @param array $bundles
     * @param ContainerInterface $container
     */
    public function bootAll(array $bundles, ContainerInterface $container): void
    {
        foreach ($bundles as $bundle) {
            $this->boot($bundle, $container);
        }
    }

    /**
     * Get service providers for a bundle.
     *
     * @param BundleInterface $bundle
     * @param ContainerInterface $container
     * @return ServiceProviderInterface[]
     */
    public function getProviders(BundleInterface $bundle, ContainerInterface $container): array
    {
        $bundleName = $bundle->getName();
        $providerKey = 'bundle.' . $bundleName . '.providers';

        if (!$container->has($providerKey)) {
            return [];
        }

        return $container->get($providerKey);
    }
}
