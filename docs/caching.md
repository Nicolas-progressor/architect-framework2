# Сервис кэширования

Architect RED 2 включает универсальный сервис кэширования с поддержкой нескольких драйверов (память, файлы, Redis) и интерфейсом, совместимым с PSR-16 (Simple Cache).

## Конфигурация

Конфигурация кэша находится в `app/config/cache.json`. Пример:

```json
{
    "default": "file",
    "prefix": "arch_cache_",
    "ttl": 3600,
    "stores": {
        "array": {
            "driver": "array"
        },
        "file": {
            "driver": "file",
            "path": "storage/cache",
            "directory_permissions": 755,
            "file_permissions": 644
        },
        "redis": {
            "driver": "redis",
            "host": "127.0.0.1",
            "port": 6379,
            "password": null,
            "database": 0,
            "timeout": 0.0,
            "persistent": false,
            "persistent_id": null
        }
    }
}
```

### Параметры

- **default** – драйвер по умолчанию (имя store).
- **prefix** – префикс для всех ключей кэша.
- **ttl** – время жизни по умолчанию в секундах.
- **stores** – конфигурации хранилищ (stores). Каждое хранилище имеет драйвер и специфичные для драйвера параметры.

### Драйверы

1. **array** – кэш в памяти (живёт в пределах одного запроса). Не требует конфигурации.
2. **file** – файловый кэш. Параметры:
   - `path` – директория для хранения файлов кэша (по умолчанию `storage/cache`).
   - `directory_permissions` – права на директории (по умолчанию 755).
   - `file_permissions` – права на файлы (по умолчанию 644).
3. **redis** – кэш в Redis. Требует расширение `phpredis`. Параметры подключения стандартные.

## Использование

### Получение экземпляра кэша

Сервис кэша регистрируется в контейнере зависимостей под именем `cache` (менеджер) и `cache.store` (хранилище по умолчанию). Также доступны конкретные хранилища: `cache.array`, `cache.file`, `cache.redis`.

#### Через контейнер

```php
use Architect\Core\Contracts\ContainerInterface;

/** @var ContainerInterface $container */
$cache = $container->get('cache.store');
```

#### Через фасад (если подключен)

```php
use Architect\Support\Facades\Cache;

$value = Cache::get('key');
```

#### Через хелпер `cache()` (если определён)

```php
$value = cache()->get('key');
```

### Основные методы

Все драйверы реализуют интерфейс `Architect\Services\Cache\Contracts\CacheInterface`, который предоставляет следующие методы:

- `get(string $key, mixed $default = null): mixed`
- `set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool`
- `delete(string $key): bool`
- `clear(): bool`
- `has(string $key): bool`
- `getMultiple(iterable $keys, mixed $default = null): iterable`
- `setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool`
- `deleteMultiple(iterable $keys): bool`

### Примеры

```php
// Сохранить значение на 5 минут
$cache->set('user:123', $user, 300);

// Получить значение
$user = $cache->get('user:123');

// Проверить наличие
if ($cache->has('user:123')) {
    // ...
}

// Удалить
$cache->delete('user:123');

// Очистить всё хранилище
$cache->clear();

// Множественные операции
$cache->setMultiple(['key1' => 'value1', 'key2' => 'value2'], 60);
$values = $cache->getMultiple(['key1', 'key2'], 'default');
```

### Работа с разными хранилищами

Менеджер кэша позволяет выбирать хранилище по имени:

```php
$fileCache = $cache->store('file');
$redisCache = $cache->store('redis');
$arrayCache = $cache->store('array');
```

## Команды CLI

Для управления кэшем из командной строки доступны следующие команды:

### Очистка кэша

```bash
php bin/arc cache:flush
```

Опции:
- `--store` – очистить только указанное хранилище (например, `--store=file`).
- `--driver` – очистить все хранилища определённого драйвера (например, `--driver=file`).

Без опций очищаются все хранилища.

### Статистика

```bash
php bin/arc cache:stats
```

Опции:
- `--store` – показать статистику для конкретного хранилища.
- `--driver` – показать статистику для всех хранилищ драйвера.

Без опций выводится общая информация по всем хранилищам.

## Интеграция с Debug панелью

Сервис кэширования интегрирован с Debug панелью Architect. На вкладке "Cache" отображается статистика использования кэша (количество операций, hit/miss ratio) для каждого драйвера.

## Расширение

Вы можете добавить собственный драйвер, зарегистрировав его через метод `extend` менеджера кэша:

```php
$cache->extend('memcached', function (array $config) {
    // Создать и вернуть экземпляр драйвера, реализующего CacheInterface
    return new MyMemcachedDriver($config);
});
```

Затем добавьте конфигурацию store с драйвером `memcached` в `cache.json`.

## Тестирование

Для тестирования рекомендуется использовать драйвер `array`, так как он изолирован в рамках одного запроса и не оставляет следов.

```php
$cache = new ArrayCacheDriver();
$cache->set('test', 'value');
$this->assertEquals('value', $cache->get('test'));
```

## Примечания

- Файловый кэш использует двухуровневую структуру директорий (первые два символа SHA1 хэша ключа) для избежания проблем с большим количеством файлов в одной директории.
- Redis драйвер требует установленного расширения `redis`. Если расширение отсутствует, попытка создать хранилище Redis выбросит исключение.
- Префикс ключей применяется ко всем операциям, что позволяет использовать один Redis сервер для нескольких приложений без конфликтов.
- TTL можно передавать как целое число (секунды) или как объект `DateInterval`. Значение `null` означает "вечно" (если драйвер поддерживает).

## Заключение

Сервис кэширования Architect предоставляет гибкий и мощный инструмент для повышения производительности приложений. Благодаря поддержке нескольких драйверов и простому API он может быть использован как для кэширования конфигураций и маршрутов, так и для пользовательских данных.