# Управление окружениями

Architect RED 2 предоставляет гибкую систему управления окружениями (environments), позволяющую настраивать поведение приложения в зависимости от среды выполнения: разработка, тестирование, staging, production. Конфигурация окружений хранится в JSON-файлах и загружается автоматически на основе переменной окружения `APP_ENV`.

## Определение окружения

Окружение определяется переменной окружения `APP_ENV`. Если переменная не установлена, используется значение по умолчанию `development`.

### Установка переменной окружения

#### В Apache/Nginx

Добавьте в конфигурацию веб-сервера:

```
SetEnv APP_ENV production
```

#### В .htaccess

```apache
SetEnv APP_ENV production
```

#### В PHP-скрипте

Можно установить переменную перед загрузкой фреймворка:

```php
putenv('APP_ENV=staging');
```

#### В Docker

```dockerfile
ENV APP_ENV=production
```

## Структура конфигурации

Конфигурационные файлы окружений находятся в директории `app/config/environment/`. Каждый файл соответствует определённому окружению:

- `development.json` – разработка
- `production.json` – продакшен
- `staging.json` – staging
- `testing.json` – тестирование

### Пример файла окружения

```json
{
    "debug": true,
    "error_reporting": "E_ALL",
    "display_errors": true,
    "log_errors": true,
    "database": {
        "default": "mysql",
        "connections": {
            "mysql": {
                "driver": "mysql",
                "host": "localhost",
                "port": 3306,
                "database": "myapp_dev",
                "username": "root",
                "password": "",
                "charset": "utf8mb4"
            }
        }
    },
    "cache": {
        "driver": "file",
        "path": "/tmp/cache/dev"
    },
    "log": {
        "level": "debug",
        "path": "/tmp/logs/dev"
    },
    "session": {
        "name": "APP_SESSION_DEV",
        "lifetime": 7200
    }
}
```

## Загрузка конфигурации

Конфигурация окружения загружается автоматически при инициализации приложения через сервис `environment`. Вы можете получить доступ к конфигурации через контейнер:

```php
$env = $container->get('environment');
$debug = $env->get('debug'); // true/false
$dbConfig = $env->get('database.connections.mysql');
```

## Переопределение параметров

Параметры окружения могут быть переопределены через переменные окружения с префиксом `ARCH_`. Например:

- `ARCH_DEBUG=true` переопределит `debug`
- `ARCH_DATABASE__CONNECTIONS__MYSQL__HOST=127.0.0.1` переопределит вложенное свойство

Имена переменных преобразуются: двойное подчёркивание `__` соответствует вложенности, а символы приводятся к нижнему регистру.

## Использование в коде

### Проверка окружения

```php
use Architect\Services\Environment\Environment;

$env = $container->get('environment');

if ($env->isDevelopment()) {
    // код для разработки
}

if ($env->isProduction()) {
    // код для продакшена
}

// Прямое сравнение
if ($env->get('debug') === true) {
    error_log('Debug mode enabled');
}
```

### Конфигурация базы данных

Конфигурация БД из окружения автоматически передаётся в Axiom ORM, если используется интеграция.

```php
$dbConfig = $env->get('database');
// Используется для инициализации соединения
```

### Настройки логирования

Уровень логирования и путь к логам определяются окружением.

```php
$logLevel = $env->get('log.level'); // 'debug', 'info', 'error'
$logPath = $env->get('log.path');
```

## Создание собственного окружения

Если вам нужно добавить новое окружение (например, `staging`), создайте файл `app/config/environment/staging.json` с нужными параметрами.

Убедитесь, что переменная `APP_ENV` установлена в `staging`.

## Безопасность

В продакшене важно отключить отладочную информацию и детализацию ошибок. Рекомендуемые настройки для `production.json`:

```json
{
    "debug": false,
    "error_reporting": "0",
    "display_errors": false,
    "log_errors": true
}
```

Также убедитесь, что пароли и секретные ключи не хранятся в репозитории. Используйте переменные окружения для чувствительных данных.

## Миграции между окружениями

При развёртывании в разных окружениях могут потребоваться разные миграции базы данных. Используйте консольные команды Axiom ORM с указанием окружения:

```bash
APP_ENV=staging php vendor/bin/axiom migrate
```

## Тестирование с разными окружениями

Для тестов можно временно установить окружение `testing`:

```php
putenv('APP_ENV=testing');
```

Создайте файл `testing.json` с конфигурацией для тестовой базы данных (например, SQLite в памяти).

## Переменные окружения по умолчанию

Architect предопределяет следующие значения для `APP_ENV`:

- `development` – разработка (по умолчанию)
- `production` – продакшен
- `staging` – промежуточная среда
- `testing` – автоматизированное тестирование

Вы можете использовать любые другие имена, но тогда нужно создать соответствующий JSON-файл.

## Интеграция с Docker

Пример `docker-compose.yml` с разными окружениями:

```yaml
version: '3.8'
services:
  app:
    build: .
    environment:
      - APP_ENV=development
    volumes:
      - ./app/config/environment/development.json:/app/app/config/environment/development.json

  app_prod:
    build: .
    environment:
      - APP_ENV=production
    volumes:
      - ./app/config/environment/production.json:/app/app/config/environment/production.json
```

## Отладка окружения

Чтобы проверить, какая конфигурация загружена, используйте отладочную панель (Debug Panel) или выполните:

```php
$env = $container->get('environment');
var_dump($env->all());
```

Также можно использовать консольную команду:

```bash
php bin/arc env:show
```

## Заключение

Система окружений Architect RED 2 позволяет гибко управлять конфигурацией приложения, обеспечивая безопасность и удобство разработки. Используйте разные настройки для разных сред, чтобы минимизировать риски и ускорить развёртывание.

Дополнительные сведения см. в разделах [Конфигурация](configuration.md) и [Развёртывание](deployment.md).