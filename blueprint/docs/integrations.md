# Интеграция с фреймворками

Blueprint легко интегрируется с различными фреймворками.

---

## Интеграция с Architect Framework

### Установка

```bash
composer require architect/blueprint
```

### ServiceProvider

Blueprint уже включает ServiceProvider для Architect:

```php
// architect/Services/Blueprint/BlueprintServiceProvider.php

namespace Architect\Services\Blueprint;

use Architect\Core\Container;
use Blueprint\Engine\Blueprint;
use Blueprint\Engine\Config\BlueprintConfig;

class BlueprintServiceProvider
{
    public function register(Container $container): void
    {
        // Регистрация конфигурации
        $container->singleton('blueprint.config', function() {
            return new BlueprintConfig([
                'debug' => env('APP_DEBUG', false),
                'cache_enabled' => !env('APP_DEBUG'),
                'cache_path' => ROOT_DIR . 'cache/views',
                'paths' => [
                    ROOT_DIR . 'app/template',
                ],
            ]);
        });
        
        // Регистрация Blueprint
        $container->singleton('blueprint', function($c) {
            $config = $c->get('blueprint.config');
            $blueprint = new Blueprint($config, $c);
            
            // Регистрация фильтров
            $this->registerFilters($blueprint);
            
            // Регистрация функций
            $this->registerFunctions($blueprint);
            
            return $blueprint;
        });
    }
    
    protected function registerFilters(Blueprint $blueprint): void
    {
        // Пользовательские фильтры
    }
    
    protected function registerFunctions(Blueprint $blueprint): void
    {
        $blueprint->registerFunction('asset', function($path) {
            return '/assets/' . ltrim($path, '/');
        });
        
        $blueprint->registerFunction('url', function($route, $params = []) {
            return url($route, $params);
        });
    }
}
```

### Bootstrap

```php
// architect/Services/Blueprint/BlueprintBootstrap.php

namespace Architect\Services\Blueprint;

class BlueprintBootstrap
{
    public static function init(): void
    {
        $container = \Architect\Core\Container::getInstance();
        
        $provider = new BlueprintServiceProvider();
        $provider->register($container);
    }
}
```

### Использование в контроллере

```php
namespace app\home\modules\home\controller;

use pattern\controller;

class home extends controller
{
    public function index_app_output(): void
    {
        $blueprint = $this->get('blueprint');
        
        echo $blueprint->render('pages/home', [
            'title' => 'Главная',
            'posts' => $this->getPosts(),
        ]);
    }
    
    private function getPosts(): array
    {
        return [];
    }
}
```

---

## Интеграция с Laravel

### ServiceProvider

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Blueprint\Engine\Blueprint;
use Blueprint\Engine\Config\BlueprintConfig;

class BlueprintServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Blueprint::class, function($app) {
            $config = new BlueprintConfig([
                'debug' => $app->environment('local'),
                'cache_enabled' => $app->environment('production'),
                'cache_path' => storage_path('framework/views'),
                'paths' => [
                    resource_path('views'),
                ],
            ]);
            
            $blueprint = new Blueprint($config, $app);
            
            $this->registerFunctions($blueprint);
            
            return $blueprint;
        });
    }
    
    protected function registerFunctions(Blueprint $blueprint): void
    {
        $blueprint->registerFunction('asset', function($path) {
            return asset($path);
        });
        
        $blueprint->registerFunction('route', function($name, $params = []) {
            return route($name, $params);
        });
        
        $blueprint->registerFunction('csrf', function() {
            return csrf_field();
        });
        
        $blueprint->registerFunction('old', function($key, $default = '') {
            return old($key, $default);
        });
    }
}
```

### Использование

```php
// В контроллере
public function index()
{
    $blueprint = app(Blueprint::class);
    
    return $blueprint->render('pages/home', [
        'title' => 'Главная',
    ]);
}
```

---

## Интеграция с Symfony

### Конфигурация services.yaml

```yaml
services:
    Blueprint\Engine\Config\BlueprintConfig:
        arguments:
            $config:
                debug: '%env(bool:APP_DEBUG)%'
                cache_enabled: '!%env(bool:APP_DEBUG)%'
                cache_path: '%kernel.cache_dir%/views'
                paths:
                    - '%kernel.project_dir%/templates'
    
    Blueprint\Engine\Blueprint:
        arguments:
            $config: '@Blueprint\Engine\Config\BlueprintConfig'
            $container: '@service_container'
```

### Использование в контроллере

```php
use Blueprint\Engine\Blueprint;

class HomeController extends AbstractController
{
    public function __construct(
        private Blueprint $blueprint
    ) {}
    
    public function index(): Response
    {
        $html = $this->blueprint->render('pages/home.html', [
            'title' => 'Главная',
        ]);
        
        return new Response($html);
    }
}
```

---

## Интеграция с Slim

```php
use Blueprint\Engine\Blueprint;
use Blueprint\Engine\Config\BlueprintConfig;

// В settings.php
$settings['blueprint'] = [
    'debug' => getenv('APP_DEBUG') === 'true',
    'cache_enabled' => getenv('APP_DEBUG') !== 'true',
    'cache_path' => __DIR__ . '/../cache/views',
    'paths' => [__DIR__ . '/../templates'],
];

// В dependencies.php
$container->set(Blueprint::class, function($c) {
    $settings = $c->get('settings')['blueprint'];
    
    return new Blueprint($settings, $c);
});

// В routes.php
$app->get('/', function($request, $response) {
    $blueprint = $this->get(Blueprint::class);
    
    $html = $blueprint->render('pages/home', [
        'title' => 'Главная',
    ]);
    
    $response->getBody()->write($html);
    return $response;
});
```

---

## Интеграция с Yii2

```php
// config/main.php
return [
    'components' => [
        'blueprint' => [
            'class' => Blueprint::class,
            'config' => [
                'debug' => YII_DEBUG,
                'cache_enabled' => !YII_DEBUG,
                'cache_path' => '@runtime/views',
                'paths' => ['@app/views'],
            ],
        ],
    ],
];

// В контроллере
public function actionIndex()
{
    $html = Yii::$app->blueprint->render('pages/home', [
        'title' => 'Главная',
    ]);
    
    return $this->renderContent($html);
}
```

---

## Создание собственного интегратора

### Интерфейс

```php
interface FrameworkIntegrationInterface
{
    public function register(object $container): void;
    public function boot(Blueprint $blueprint): void;
}
```

### Реализация

```php
class CustomFrameworkIntegration implements FrameworkIntegrationInterface
{
    private object $container;
    
    public function register(object $container): void
    {
        $this->container = $container;
        
        // Регистрация Blueprint в контейнере
        $container->set('blueprint', function() {
            return $this->createBlueprint();
        });
    }
    
    public function boot(Blueprint $blueprint): void
    {
        // Регистрация фильтров и функций
        $this->registerFilters($blueprint);
        $this->registerFunctions($blueprint);
        $this->registerGlobals($blueprint);
    }
    
    protected function createBlueprint(): Blueprint
    {
        $config = $this->getConfig();
        return new Blueprint($config, $this->container);
    }
    
    protected function getConfig(): BlueprintConfig
    {
        return new BlueprintConfig([
            'debug' => $this->container->get('config')->get('app.debug'),
            'cache_enabled' => !$this->container->get('config')->get('app.debug'),
            'cache_path' => $this->container->get('config')->get('paths.cache') . '/views',
            'paths' => $this->container->get('config')->get('paths.templates'),
        ]);
    }
    
    protected function registerFilters(Blueprint $blueprint): void
    {
        // ...
    }
    
    protected function registerFunctions(Blueprint $blueprint): void
    {
        // ...
    }
    
    protected function registerGlobals(Blueprint $blueprint): void
    {
        $blueprint->addGlobals([
            'app' => $this->container->get('config')->get('app'),
        ]);
    }
}
```

---

## Общие паттерны интеграции

### Регистрация хелперов

```php
$blueprint->registerFunction('url', function($route, $params = []) use ($router) {
    return $router->url($route, $params);
});

$blueprint->registerFunction('asset', function($path) use ($config) {
    return $config->get('app.url') . '/assets/' . $path;
});
```

### Глобальные переменные

```php
$blueprint->addGlobals([
    'app' => [
        'name' => $config->get('app.name'),
        'env' => $config->get('app.env'),
        'debug' => $config->get('app.debug'),
    ],
    'user' => $auth->user(),
]);
```

### Рендеринг в базовом контроллере

```php
abstract class BaseController
{
    protected Blueprint $view;
    
    public function __construct(Blueprint $view)
    {
        $this->view = $view;
    }
    
    protected function render(string $template, array $data = []): string
    {
        return $this->view->render($template, $data);
    }
}
```
