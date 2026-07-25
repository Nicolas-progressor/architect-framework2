# План реализации системы бандлов для Architect Framework

## Обзор
Система бандлов вдохновлена Symfony и предоставляет модульный подход к организации кода приложения. Каждый бандл представляет собой структурированный пакет функциональности, который может включать в себя контроллеры, модели, представления, конфигурации, сервисы и другие компоненты.

## Архитектура системы бандлов

### 1. Интерфейс BundleInterface
Файл: `architect/Contracts/BundleInterface.php`

```php
<?php

declare(strict_types=1);

namespace Architect\Contracts;

use Architect\Core\Contracts\ContainerInterface;
use Architect\Core\Contracts\FrameworkInterface;

/**
 * Interface for bundles that extend the framework functionality.
 */
interface BundleInterface
{
    /**
     * Get the bundle name.
     */
    public function getName(): string;

    /**
     * Register services into the container.
     */
    public function register(ContainerInterface $container): void;

    /**
     * Boot the bundle after registration.
     */
    public function boot(ContainerInterface $container, FrameworkInterface $framework): void;

    /**
     * Shutdown the bundle.
     */
    public function shutdown(ContainerInterface $container, FrameworkInterface $framework): void;
}
```

### 2. Абстрактный класс бандла
Файл: `architect/Support/AbstractBundle.php`

```php
<?php

declare(strict_types=1);

namespace Architect\Support;

use Architect\Contracts\BundleInterface;
use Architect\Core\Contracts\ContainerInterface;
use Architect\Core\Contracts\FrameworkInterface;

/**
 * Base bundle class with common functionality.
 */
abstract class AbstractBundle implements BundleInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        // Default implementation based on class name
        $class = (new \ReflectionClass($this))->getShortName();
        return preg_replace('/Bundle$/', '', $class);
    }

    /**
     * {@inheritdoc}
     */
    public function register(ContainerInterface $container): void
    {
        // Default implementation does nothing
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $container, FrameworkInterface $framework): void
    {
        // Default implementation does nothing
    }

    /**
     * {@inheritdoc}
     */
    public function shutdown(ContainerInterface $container, FrameworkInterface $framework): void
    {
        // Default implementation does nothing
    }
}
```

### 3. Менеджер бандлов
Файл: `architect/Core/BundleManager.php`

```php
<?php

declare(strict_types=1);

namespace Architect\Core;

use Architect\Contracts\BundleInterface;
use Architect\Core\Contracts\ContainerInterface;
use Architect\Core\Contracts\FrameworkInterface;

/**
 * Manages bundle registration, booting and shutdown.
 */
class BundleManager
{
    /** @var BundleInterface[] */
    private array $bundles = [];

    /** @var bool */
    private bool $booted = false;

    /**
     * Register a bundle.
     */
    public function register(BundleInterface $bundle): void
    {
        $this->bundles[$bundle->getName()] = $bundle;
    }

    /**
     * Get all registered bundles.
     *
     * @return BundleInterface[]
     */
    public function getBundles(): array
    {
        return $this->bundles;
    }

    /**
     * Get a bundle by name.
     */
    public function getBundle(string $name): ?BundleInterface
    {
        return $this->bundles[$name] ?? null;
    }

    /**
     * Register all bundle services.
     */
    public function registerBundles(ContainerInterface $container): void
    {
        foreach ($this->bundles as $bundle) {
            $bundle->register($container);
        }
    }

    /**
     * Boot all bundles.
     */
    public function bootBundles(ContainerInterface $container, FrameworkInterface $framework): void
    {
        if ($this->booted) {
            return;
        }

        foreach ($this->bundles as $bundle) {
            $bundle->boot($container, $framework);
        }

        $this->booted = true;
    }

    /**
     * Shutdown all bundles.
     */
    public function shutdownBundles(ContainerInterface $container, FrameworkInterface $framework): void
    {
        foreach ($this->bundles as $bundle) {
            $bundle->shutdown($container, $framework);
        }
    }
}
```

### 4. Автообнаружение бандлов
Файл: `architect/Core/Bundle/BundleDiscovery.php`

```php
<?php

declare(strict_types=1);

namespace Architect\Core\Bundle;

/**
 * Discovers bundles from installed Composer packages.
 */
class BundleDiscovery
{
    /**
     * Discover all bundles from installed packages.
     *
     * @return string[] Array of fully qualified bundle class names
     */
    public static function discover(): array
    {
        $bundles = [];

        // Get root package extra config
        $rootPackage = self::getComposerRootPackage();
        if (isset($rootPackage['extra']['architect']['bundles'])) {
            $bundles = array_merge($bundles, $rootPackage['extra']['architect']['bundles']);
        }

        // Get installed packages
        $packages = self::getInstalledPackages();
        foreach ($packages as $package) {
            if (isset($package['extra']['architect']['bundles'])) {
                $bundles = array_merge($bundles, $package['extra']['architect']['bundles']);
            }
        }

        // Remove duplicates and ensure strings
        $bundles = array_unique(array_filter($bundles, 'is_string'));

        // Sort for deterministic output
        sort($bundles);

        return $bundles;
    }

    /**
     * Get Composer root package extra data.
     *
     * @return array
     */
    private static function getComposerRootPackage(): array
    {
        static $rootPackage = null;
        if ($rootPackage === null) {
            $composerFile = ROOT_DIR . 'composer.json';
            if (!file_exists($composerFile)) {
                $rootPackage = [];
                return $rootPackage;
            }
            $content = file_get_contents($composerFile);
            $data = json_decode($content, true);
            $rootPackage = is_array($data) ? $data : [];
        }
        return $rootPackage;
    }

    /**
     * Get installed packages data.
     *
     * @return array[]
     */
    private static function getInstalledPackages(): array
    {
        // Use Composer's InstalledVersions if available (Composer 2)
        if (class_exists('Composer\InstalledVersions')) {
            $packages = [];
            $rootPackageName = InstalledVersions::getRootPackage()['name'] ?? '';
            foreach (InstalledVersions::getAllRawData() as $vendor) {
                foreach ($vendor['versions'] as $packageName => $packageData) {
                    // Skip root package
                    if ($packageName === $rootPackageName) {
                        continue;
                    }
                    if (isset($packageData['install_path'])) {
                        $composerFile = $packageData['install_path'] . '/composer.json';
                        if (file_exists($composerFile)) {
                            $content = file_get_contents($composerFile);
                            $data = json_decode($content, true);
                            if (is_array($data)) {
                                $packages[] = $data;
                            }
                        }
                    }
                }
            }
            return $packages;
        }

        // Fallback: scan vendor/composer/installed.json
        $installedFile = ROOT_DIR . 'vendor/composer/installed.json';
        if (file_exists($installedFile)) {
            $content = file_get_contents($installedFile);
            $data = json_decode($content, true);
            if (isset($data['packages'])) {
                return $data['packages'];
            }
            if (isset($data['dev-packages'])) {
                return array_merge($data['packages'] ?? [], $data['dev-packages']);
            }
        }

        return [];
    }
}
```

## Структура бандла

```
src/
├── Bundle/
│   ├── MyBundle/
│   │   ├── Controller/
│   │   ├── Model/
│   │   ├── View/
│   │   ├── Config/
│   │   ├── Resources/
│   │   │   ├── views/
│   │   │   ├── translations/
│   │   │   └── public/
│   │   ├── MyBundle.php
│   │   └── composer.json
```

## Интеграция с фреймворком

### Обновление Framework.php
Файл: `architect/Core/Framework.php`

Добавить поддержку BundleManager:

```php
// В конструкторе
private BundleManager $bundleManager;

public function __construct(
    ContainerInterface $container,
    StatementInterface $statement
) {
    $this->container = $container;
    $this->statement = $statement;
    $this->bundleManager = new BundleManager();
    
    $this->container->set('statement', $this->statement);
    $this->container->set('framework', $this);
    $this->container->set('bundle.manager', $this->bundleManager);
}

// Добавить методы для работы с бандлами
public function getBundleManager(): BundleManager
{
    return $this->bundleManager;
}

public function registerBundle(BundleInterface $bundle): void
{
    $this->bundleManager->register($bundle);
}
```

## Конфигурация

### composer.json
Добавить поддержку бандлов в composer.json:

```json
{
    "extra": {
        "architect": {
            "bundles": [
                "App\\Bundle\\MyBundle\\MyBundle"
            ]
        }
    }
}
```

## Использование

### Регистрация бандлов
В bootstrap файле:

```php
$framework = new Framework($container, $statement);

// Автоматическое обнаружение бандлов
$bundleClasses = BundleDiscovery::discover();
foreach ($bundleClasses as $bundleClass) {
    if (class_exists($bundleClass)) {
        $bundle = new $bundleClass();
        $framework->registerBundle($bundle);
    }
}

// Или ручная регистрация
$framework->registerBundle(new MyBundle());
```

## Расширение функциональности

### 1. Конфигурация бандлов
Каждый бандл может иметь свою конфигурацию, которая загружается из файлов в директории Config.

### 2. Сервисы бандлов
Бандлы могут регистрировать свои сервисы в контейнере через метод register().

### 3. Маршруты бандлов
Бандлы могут регистрировать свои маршруты.

### 4. Команды бандлов
Бандлы могут регистрировать свои консольные команды.

### 5. Ресурсы бандлов
Бандлы могут содержать статические ресурсы, которые могут быть опубликованы в публичной директории.