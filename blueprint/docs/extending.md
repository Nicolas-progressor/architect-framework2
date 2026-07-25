# Расширение Blueprint

Blueprint легко расширяется через фильтры и функции.

---

## Регистрация фильтров

### Простой фильтр

```php
use Blueprint\Engine\Blueprint;

$blueprint = new Blueprint($config);

$blueprint->registerFilter('rot13', function($value) {
    return str_rot13((string) $value);
});
```

Использование:

```blade
{{ text | rot13 }}
```

### Фильтр с аргументами

```php
$blueprint->registerFilter('prefix', function($value, $prefix, $suffix = '') {
    return $prefix . $value . $suffix;
});
```

```blade
{{ name | prefix('Hello, ', '!') }}
{# Hello, John! #}
```

### Фильтр с зависимостями

```php
$blueprint->registerFilter('money', function($value, $currency = 'USD') use ($formatter) {
    return $formatter->format((float) $value, $currency);
});
```

---

## Регистрация функций

### Простая функция

```php
$blueprint->registerFunction('asset', function($path) {
    return '/assets/' . ltrim($path, '/');
});
```

```blade
<link href="{{ asset('css/style.css') }}" rel="stylesheet">
<script src="{{ asset('js/app.js') }}"></script>
```

### Функция с несколькими аргументами

```php
$blueprint->registerFunction('url', function($route, $params = []) {
    $url = '/' . ltrim($route, '/');
    
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    return $url;
});
```

```blade
<a href="{{ url('user/profile', {id: user.id}) }}">
    Профиль
</a>
```

### Функция с зависимостями

```php
$blueprint->registerFunction('csrf', function() use ($session) {
    return '<input type="hidden" name="_token" value="' . $session->token() . '">';
});
```

```blade
<form method="POST">
    {{ csrf() | raw }}
    ...
</form>
```

---

## Создание класса фильтров

### Класс с фильтрами

```php
<?php

namespace App\Template\Filters;

class MyFilters
{
    public static function rot13(string $value): string
    {
        return str_rot13($value);
    }
    
    public static function money(float $value, string $currency = 'USD'): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'RUB' => '₽',
        ];
        
        return ($symbols[$currency] ?? '') . number_format($value, 2);
    }
    
    public static function phone(string $value): string
    {
        // Форматирование телефона
        $value = preg_replace('/\D/', '', $value);
        
        if (strlen($value) === 11) {
            return sprintf(
                '+%s (%s) %s-%s-%s',
                substr($value, 0, 1),
                substr($value, 1, 3),
                substr($value, 4, 3),
                substr($value, 7, 2),
                substr($value, 9, 2)
            );
        }
        
        return $value;
    }
}
```

### Регистрация класса

```php
use App\Template\Filters\MyFilters;

$blueprint->registerFilter('rot13', [MyFilters::class, 'rot13']);
$blueprint->registerFilter('money', [MyFilters::class, 'money']);
$blueprint->registerFilter('phone', [MyFilters::class, 'phone']);
```

---

## Создание класса функций

```php
<?php

namespace App\Template\Functions;

use App\Services\UrlGenerator;
use App\Services\SessionManager;

class MyFunctions
{
    private UrlGenerator $urlGenerator;
    private SessionManager $session;
    
    public function __construct(UrlGenerator $urlGenerator, SessionManager $session)
    {
        $this->urlGenerator = $urlGenerator;
        $this->session = $session;
    }
    
    public function url(string $route, array $params = []): string
    {
        return $this->urlGenerator->to($route, $params);
    }
    
    public function route(string $name, array $params = []): string
    {
        return $this->urlGenerator->route($name, $params);
    }
    
    public function csrf(): string
    {
        $token = $this->session->token();
        return '<input type="hidden" name="_token" value="' . htmlspecialchars($token) . '">';
    }
    
    public function old(string $key, $default = ''): string
    {
        return $this->session->getOldInput($key, $default);
    }
}
```

### Регистрация

```php
use App\Template\Functions\MyFunctions;

$functions = new MyFunctions($urlGenerator, $session);

$blueprint->registerFunction('url', [$functions, 'url']);
$blueprint->registerFunction('route', [$functions, 'route']);
$blueprint->registerFunction('csrf', [$functions, 'csrf']);
$blueprint->registerFunction('old', [$functions, 'old']);
```

---

## Расширение через ServiceProvider

```php
<?php

namespace App\Providers;

use Blueprint\Engine\Blueprint;
use Blueprint\Engine\Contracts\FilterRegistryInterface;
use Blueprint\Engine\Contracts\FunctionRegistryInterface;

class BlueprintServiceProvider
{
    public function register(Blueprint $blueprint): void
    {
        $this->registerFilters($blueprint);
        $this->registerFunctions($blueprint);
        $this->registerGlobals($blueprint);
    }
    
    protected function registerFilters(Blueprint $blueprint): void
    {
        $blueprint->registerFilter('money', function($value, $currency = 'USD') {
            // ...
        });
        
        $blueprint->registerFilter('phone', function($value) {
            // ...
        });
    }
    
    protected function registerFunctions(Blueprint $blueprint): void
    {
        $blueprint->registerFunction('asset', function($path) {
            return asset($path);
        });
        
        $blueprint->registerFunction('url', function($route, $params = []) {
            return url($route, $params);
        });
    }
    
    protected function registerGlobals(Blueprint $blueprint): void
    {
        $blueprint->addGlobals([
            'app' => [
                'name' => config('app.name'),
                'env' => config('app.env'),
            ],
        ]);
    }
}
```

---

## Примеры полезных фильтров

### Markdown

```php
$blueprint->registerFilter('markdown', function($value) use ($parser) {
    return $parser->parse((string) $value);
});
```

```blade
{{ content | markdown | raw }}
```

### Truncate по словам

```php
$blueprint->registerFilter('words', function($value, $limit = 50, $end = '...') {
    $words = preg_split('/\s+/', trim($value));
    
    if (count($words) <= $limit) {
        return $value;
    }
    
    return implode(' ', array_slice($words, 0, $limit)) . $end;
});
```

```blade
{{ text | words(20) }}
```

### Plural

```php
$blueprint->registerFilter('plural', function($count, $forms) {
    // forms: "товар|товара|товаров"
    $forms = explode('|', $forms);
    $n = abs($count) % 100;
    $n1 = $n % 10;
    
    if ($n > 10 && $n < 20) return $count . ' ' . $forms[2];
    if ($n1 > 1 && $n1 < 5) return $count . ' ' . $forms[1];
    if ($n1 == 1) return $count . ' ' . $forms[0];
    
    return $count . ' ' . $forms[2];
});
```

```blade
{{ count | plural('товар|товара|товаров') }}
```

---

## Примеры полезных функций

### config()

```php
$blueprint->registerFunction('config', function($key, $default = null) {
    return config($key, $default);
});
```

```blade
<title>{{ config('app.name') }}</title>
```

### auth()

```php
$blueprint->registerFunction('auth', function() {
    return auth()->user();
});
```

```blade
{% if auth() %}
    Привет, {{ auth().name }}!
{% endif %}
```

### trans()

```php
$blueprint->registerFunction('trans', function($key, $params = []) {
    return trans($key, $params);
});
```

```blade
{{ trans('messages.welcome', {name: user.name}) }}
```
