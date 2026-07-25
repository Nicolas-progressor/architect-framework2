# Производительность и оптимизация

Производительность веб-приложения критически важна для пользовательского опыта и SEO. Architect RED 2 предоставляет ряд возможностей для оптимизации, от кэширования до сжатия ответов. В этой главе описаны лучшие практики и инструменты для повышения производительности приложений на Architect.

## Кэширование

### Кэширование шаблонов Blueprint

Blueprint компилирует шаблоны в PHP-код и может кэшировать скомпилированные файлы. Включите кэширование в конфигурации:

```json
{
    "engine": "blueprint",
    "cache": true,
    "cache_path": "cache/views",
    "cache_ttl": 3600
}
```

При изменении шаблонов необходимо очистить кэш:

```php
$blueprint = $container->get('blueprint');
$blueprint->clearCache();
```

Или через консоль:

```bash
php bin/arc cache:clear
```

### Кэширование данных

Используйте сервис кэширования, например `symfony/cache` или `illuminate/cache`. Architect не включает встроенный кэш, но легко интегрируется с любым PSR-6/PSR-16 совместимым кэшером.

Пример интеграции с Symfony Cache:

```bash
composer require symfony/cache
```

Регистрация в контейнере:

```php
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

$this->container->factory('cache', function() {
    return new FilesystemAdapter('app', 3600, 'cache/');
});
```

Использование:

```php
$cache = $container->get('cache');
$item = $cache->getItem('user_123');
if (!$item->isHit()) {
    $data = $this->model->getUser(123);
    $item->set($data);
    $cache->save($item);
}
$user = $item->get();
```

### Кэширование запросов к БД

Axiom ORM поддерживает кэширование результатов запросов через модуль `axiom/cache`.

```php
use Axiom\Orm\Orm;

$users = Orm::table('users')
    ->where('status', '=', 'active')
    ->cache(300) // кэшировать на 5 минут
    ->get();
```

## Оптимизация базы данных

### Индексы

Убедитесь, что часто используемые столбцы в условиях `WHERE`, `ORDER BY`, `JOIN` проиндексированы.

```sql
CREATE INDEX idx_users_status ON users(status);
CREATE INDEX idx_orders_user_id ON orders(user_id);
```

### Оптимизация запросов

Используйте методы Axiom ORM для построения эффективных запросов:

- `select()` только нужные столбцы
- `limit()` для пагинации
- `join()` вместо вложенных запросов, где это уместно
- `whereRaw()` для сложных условий, но осторожно с инъекциями

### Репликация и шардирование

Для высоконагруженных приложений рассмотрите использование репликации (master-slave) или шардирования. Axiom ORM поддерживает несколько соединений, вы можете настроить их в конфигурации.

```json
{
    "default": "mysql_write",
    "connections": {
        "mysql_write": {
            "driver": "mysql",
            "host": "master.db.example.com"
        },
        "mysql_read": {
            "driver": "mysql",
            "host": "slave.db.example.com"
        }
    }
}
```

В коде можно выбирать соединение:

```php
Orm::connection('mysql_read')->table('users')->get();
```

## Оптимизация PHP

### OPCache

Включите OPCache в производственном окружении. Настройки в `php.ini`:

```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0 ; в production
```

### Предзагрузка классов (Preloading)

Используйте preloading для загрузки часто используемых классов в память. Создайте скрипт `preload.php`:

```php
<?php
// preload.php
opcache_compile_file('vendor/autoload.php');
opcache_compile_file('architect/bootstrap.php');
// ... другие файлы
```

И укажите его в `php.ini`:

```ini
opcache.preload=/path/to/preload.php
```

### Оптимизация автозагрузки Composer

Используйте оптимизированный автозагрузчик:

```bash
composer dump-autoload --optimize
```

Или для production:

```bash
composer dump-autoload --classmap-authoritative
```

## Оптимизация фронтенда

### Минификация и конкатенация CSS/JS

Используйте хелпер `Assets` для управления ресурсами. Вы можете настроить конкатенацию и минификацию через конфигурацию.

```php
Statics::Assets()->css(['style.css', 'theme.css'], ['minify' => true]);
Statics::Assets()->js(['app.js', 'widgets.js'], ['concat' => true]);
```

Для production можно использовать сборщики (Vite, Webpack) и помещать скомпилированные файлы в `htdocs/assets/dist/`.

### Сжатие ответов

Включите gzip-сжатие на уровне веб-сервера (Nginx/Apache) или через PHP.

Пример для Nginx:

```nginx
gzip on;
gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;
```

### Кэширование статических файлов

Настройте долгое кэширование для статических ресурсов:

```nginx
location ~* \.(jpg|jpeg|png|gif|ico|css|js)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}
```

## Мониторинг производительности

### Отладочная панель

Debug Panel включает вкладку **Performance**, где отображается время выполнения запроса, использование памяти, количество SQL-запросов и их длительность.

### Логирование медленных запросов

Настройте логгер для записи запросов, выполняющихся дольше определённого порога.

```php
$logger = $container->get('logger');
$start = microtime(true);
// ... выполнение действия
$time = microtime(true) - $start;
if ($time > 1.0) {
    $logger->warning('Slow request', ['time' => $time, 'uri' => $request->getUri()]);
}
```

### Профилирование с XHProf или Blackfire

Интегрируйте профилировщик для детального анализа производительности.

Пример с XHProf:

```php
if (extension_loaded('xhprof')) {
    xhprof_enable();
    register_shutdown_function(function() {
        $data = xhprof_disable();
        // сохранить $data для анализа
    });
}
```

## Асинхронная обработка

### Очереди задач

Для длительных операций (отправка email, обработка изображений) используйте очереди. Architect можно интегрировать с RabbitMQ, Redis или базами данных через `symfony/messenger`.

```php
use Symfony\Component\Messenger\MessageBusInterface;

$bus = $container->get('message_bus');
$bus->dispatch(new ProcessImageMessage($imageId));
```

### Фоновые workers

Запустите воркеры для обработки очередей:

```bash
php bin/console messenger:consume async
```

## Кэширование HTTP-ответов

### Кэширование на стороне сервера

Используйте middleware для кэширования целых страниц. Пример простого кэширования:

```php
class CacheMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        $key = 'page_' . md5($request->getUri());
        $cached = $this->cache->get($key);
        if ($cached) {
            return new Response($cached);
        }
        $response = $handler->handle($request);
        $this->cache->set($key, $response->getContent(), 300);
        return $response;
    }
}
```

### HTTP-кэширование (ETag, Last-Modified)

Реализуйте поддержку ETag для статических ресурсов.

```php
$etag = md5($content);
if ($request->getHeader('If-None-Match') === $etag) {
    return new Response('', 304);
}
$response = new Response($content);
$response->setHeader('ETag', $etag);
```

## Оптимизация сессий

По умолчанию сессии хранятся в файлах. Для повышения производительности переключитесь на Redis или Memcached.

Конфигурация в `app/config/environment/production.json`:

```json
{
    "session": {
        "driver": "redis",
        "host": "127.0.0.1",
        "port": 6379,
        "prefix": "sess_"
    }
}
```

## Сжатие изображений

Используйте библиотеки (например, `intervention/image`) для автоматического сжатия загружаемых изображений.

```php
use Intervention\Image\ImageManager;

$manager = new ImageManager();
$image = $manager->make('uploaded.jpg');
$image->resize(800, 600)->save('optimized.jpg', 80);
```

## Рекомендации по коду

- **Избегайте N+1 проблемы в запросах**: Используйте жадную загрузку (`with()` в Axiom).
- **Минимизируйте использование глобальных переменных**.
- **Используйте ранний возврат** (early return) в условиях.
- **Кэшируйте результаты тяжёлых вычислений**.
- **Избегайте сериализации больших объектов** в сессиях.

## Инструменты для аудита

- **Lighthouse** – аудит производительности фронтенда.
- **WebPageTest** – тестирование скорости загрузки.
- **New Relic / DataDog** – мониторинг производительности в реальном времени.
- **Blackfire** – профилирование PHP.

## Заключение

Оптимизация производительности – непрерывный процесс. Начните с измерения ключевых метрик, определите узкие места и применяйте соответствующие техники. Architect RED 2 предоставляет гибкость для внедрения различных стратегий кэширования, асинхронной обработки и мониторинга.

Дополнительные сведения см. в разделах [Конфигурация](configuration.md) и [Развёртывание](deployment.md).