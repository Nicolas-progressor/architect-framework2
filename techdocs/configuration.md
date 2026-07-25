# Конфигурация

Конфигурация в Architect RED 2 основана на JSON-файлах, что обеспечивает простоту, читаемость и возможность версионирования. Система поддерживает несколько окружений (development, testing, staging, production) и каскадное слияние настроек.

## Структура конфигурационных файлов

Конфигурационные файлы расположены в директории `app/config/`:

```
app/config/
├── config.json              # Общие настройки приложения
├── apps.json                # Конфигурация приложений
├── router.json              # Настройки роутера
├── debug.json               # Конфигурация отладочной панели
├── blueprint.json           # Настройки шаблонизатора Blueprint
├── auth.json                # Настройки системы аутентификации
├── database.json            # Настройки базы данных для ORM Axiom
└── environment/             # Окружения
    ├── development.json
    ├── testing.json
    ├── staging.json
    └── production.json
```

## Общие настройки (config.json)

Файл `config.json` содержит общие настройки, которые используются во всех окружениях, если не переопределены.

```json
{
    "app": {
        "name": "My Application",
        "version": "1.0.0",
        "url": "http://localhost",
        "timezone": "Europe/Moscow"
    },
    "database": {
        "host": "localhost",
        "port": 3306,
        "name": "myapp",
        "charset": "utf8mb4"
    },
    "cache": {
        "driver": "file",
        "path": "/tmp/cache"
    },
    "session": {
        "name": "ARCHECT_SESSION",
        "lifetime": 7200
    },
    "log": {
        "level": "info",
        "path": "/tmp/logs"
    }
}
```

## Окружения (environment/{env}.json)

Настройки окружения переопределяют общие настройки. Например, `development.json`:

```json
{
    "debug": true,
    "error_reporting": "E_ALL",
    "display_errors": true,
    "log_errors": true,
    "database": {
        "name": "myapp_dev"
    },
    "cache": {
        "path": "/tmp/cache/dev"
    },
    "log": {
        "level": "debug"
    }
}
```

**production.json**:

```json
{
    "debug": false,
    "error_reporting": "E_ALL & ~E_NOTICE & ~E_DEPRECATED",
    "display_errors": false,
    "log_errors": true,
    "database": {
        "name": "myapp_production"
    },
    "cache": {
        "driver": "redis"
    },
    "log": {
        "level": "error"
    }
}
```

## Определение окружения

Окружение определяется по приоритетной цепочке:

1. **Переменная окружения ОС** `APP_ENV`
2. **Файл `.env`** в корне проекта
3. **Константа PHP** `APP_ENV`
4. **Значение по умолчанию** — `production`

Файл `.env` (пример):

```bash
# Окружение
APP_ENV=development

# База данных
DB_HOST=localhost
DB_PORT=3306
DB_NAME=arcred_dev
DB_USER=root
DB_PASSWORD=

# Кэш
CACHE_DRIVER=file

# Секретный ключ
APP_KEY=your-secret-key-here

# URL приложения
APP_URL=http://localhost
```

## Конфигурация приложений (apps.json)

Файл `apps.json` определяет список приложений и их свойства.

```json
{
    "apps": [
        {
            "name": "home",
            "path": "home",
            "default": true
        },
        {
            "name": "admin",
            "path": "admin",
            "default": false
        }
    ],
    "default_app": "home"
}
```

## Конфигурация роутера (router.json)

```json
{
    "default_module": "home",
    "default_controller": "home",
    "default_action": "index",
    "404_module": "_404",
    "404_controller": "_404",
    "404_action": "index"
}
```
Каждое приложение может иметь собственный файл `router.json` в папке `apps/{app}/config/router.json`, который переопределяет глобальные настройки для этого приложения. Например, приложение `admin` может задать свои значения по умолчанию и обработчик 404. Настройки мержатся рекурсивно, поэтому можно переопределить только нужные поля.


## Конфигурация отладочной панели (debug.json)

```json
{
    "enabled": true,
    "log_to_file": true,
    "log_categories": ["info", "warning", "error"],
    "show_queries": true,
    "show_memory": true,
    "show_time": true,
    "show_includes": true,
    "show_session": true,
    "show_cookies": true,
    "collect_custom_data": true,
    "ip_whitelist": [],
    "max_messages": 1000,
    "max_data_size": 1048576,
    "auto_refresh": false,
    "auto_refresh_interval": 2000
}
```

## Конфигурация Blueprint (blueprint.json)

```json
{
    "debug": false,
    "cache_enabled": false,
    "cache": "cache/blueprints",
    "extensions": [".blu", ".html"],
    "paths": [
        "app/template"
    ]
}
```

## Конфигурация аутентификации (auth.json)

```json
{
    "driver": "database",
    "table_prefix": "auth_",
    "session_lifetime": 1440,
    "password_hash_algorithm": "bcrypt",
    "password_cost": 12,
    "jwt_secret": "your-jwt-secret-key",
    "jwt_ttl": 3600,
    "default_role": "guest",
    "roles": {
        "admin": {
            "permissions": ["*"],
            "description": "Administrator with full access"
        },
        "user": {
            "permissions": ["view_posts", "create_posts"],
            "description": "Regular user"
        },
        "guest": {
            "permissions": ["view_public_content"],
            "description": "Guest user"
        }
    },
    "permissions": {
        "view_posts": "View posts",
        "create_posts": "Create new posts",
        "edit_posts": "Edit own posts",
        "delete_posts": "Delete own posts",
        "manage_users": "Manage users",
        "view_public_content": "View public content"
    }
}
```

## Конфигурация базы данных (database.json)

```json
{
    "default": "mysql",
    "connections": {
        "mysql": {
            "driver": "mysql",
            "host": "localhost",
            "port": 3306,
            "database": "myapp",
            "username": "root",
            "password": "secret",
            "charset": "utf8mb4"
        },
        "pgsql": {
            "driver": "pgsql",
            "host": "db.example.com",
            "port": 5432,
            "database": "app_prod",
            "username": "user",
            "password": "pass",
            "schema": "public"
        }
    },
    "options": {
        "fetch_mode": "PDO::FETCH_ASSOC",
        "debug": true
    }
}
```

## Доступ к конфигурации

### Через EnvironmentManager

```php
$env = $container->get('environment');

// Получить значение
$appName = $env->get('app.name');
$dbHost = $env->get('database.host');

// Проверить окружение
if ($env->isDevelopment()) {
    // код для разработки
}

// Все настройки
$config = $env->all();
```

### Через Config сервис

```php
$config = $container->get('config');

$appConfig = $config->getAppConfig();
$value = $config->get('key', 'default');
$config->set('key', 'value');
```

## Слияние конфигураций

Настройки из `environment/{env}.json` переопределяют `config.json`. Слияние рекурсивное.

Пример:

**config.json**:
```json
{
    "database": {
        "host": "localhost",
        "port": 3306,
        "name": "myapp"
    }
}
```

**development.json**:
```json
{
    "database": {
        "name": "myapp_dev"
    }
}
```

Результат:
```php
$env->get('database.host')  // "localhost" (унаследовано)
$env->get('database.port')  // 3306 (унаследовано)
$env->get('database.name')  // "myapp_dev" (переопределено)
```

## Динамическое изменение конфигурации

Конфигурация загружается один раз при старте приложения (ленивая загрузка). Для динамического изменения можно использовать метод `set` сервиса Config:

```php
$config = $container->get('config');
$config->set('app.name', 'New Name');
```

Однако изменения не сохраняются в файлы и действуют только в течение текущего запроса.

## Рекомендации

- Храните секретные данные (пароли, ключи) в `.env` файле, а не в JSON-конфигурации.
- Используйте разные настройки для каждого окружения.
- Для production отключайте debug и display_errors.
- Валидируйте JSON-файлы с помощью JSON Schema (опционально).