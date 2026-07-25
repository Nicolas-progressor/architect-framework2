# Сервис Connect: Базовая система работы с БД

## Обзор

Сервис Connect — это встроенный в Architect Framework компонент для прямого взаимодействия с реляционными базами данных через PDO. Он предоставляет простой, но мощный API для выполнения запросов, управления транзакциями и логгирования, не зависящий от Axiom ORM.

**Ключевые особенности:**

- Поддержка нескольких драйверов (MySQL, PostgreSQL, SQLite) через единый интерфейс.
- Менеджер соединений с конфигурацией из JSON‑файла.
- Полноценная обёртка над PDO с методами `query`, `execute`, `fetch`, `fetchAll`.
- Транзакции с вложенностью (через стандартный PDO).
- Интеграция с системой логгирования Architect через `QueryLoggerInterface`.
- Построение DSN вынесено в отдельный класс `DsnBuilder`.
- Полная поддержка Dependency Injection и PSR‑стандартов.

## Архитектура

### Компоненты

1. **`DatabaseInterface`** (`architect/Services/Database/Contracts/DatabaseInterface.php`)  
   Определяет контракт для работы с БД: основные методы запросов, транзакций, получения PDO.

2. **`Database`** (`architect/Services/Database/Database.php`)  
   Основной класс соединения. Реализует `DatabaseInterface`, инкапсулирует PDO, использует `DsnBuilder` для построения DSN и `QueryLoggerInterface` для логгирования запросов.

3. **`DatabaseManager`** (`architect/Services/Database/DatabaseManager.php`)  
   Менеджер множественных соединений. Загружает конфигурацию из `config.database`, создаёт и кеширует экземпляры `Database`. Предоставляет методы для получения соединения по имени, управления транзакциями по умолчанию, установки глобального логгера.

4. **`DsnBuilder`** (`architect/Services/Database/DsnBuilder.php`)  
   Утилитарный класс, преобразующий массив конфигурации в DSN‑строку для PDO. Поддерживает драйверы `mysql`, `pgsql`, `sqlite`.

5. **`QueryLoggerInterface`** (`architect/Services/Database/Contracts/QueryLoggerInterface.php`)  
   Интерфейс для логгирования запросов. Заменяет статический callback, позволяет внедрять любую реализацию логгера.

6. **`DatabaseServiceProvider`** (`architect/Services/Database/DatabaseServiceProvider.php`)  
   Сервис‑провайдер, регистрирующий `DatabaseManager` в контейнере зависимостей. Автоматически загружает конфигурацию через `config.loader` (или `config.database`), настраивает интеграцию с Debug‑сервисом для логгирования запросов.

### Взаимодействие с контейнером

Сервис регистрируется под именами:

- `database` — экземпляр `DatabaseManager`.
- `db` — алиас для `database`.

Конфигурация ожидается в `config.database` (объект `ConfigRepository`) или загружается через `config.loader` из файла `app/config/database.json`.

### Конфигурация

Файл `app/config/database.json` должен иметь следующую структуру:

```json
{
    "default": "mysql",
    "connections": {
        "mysql": {
            "driver": "mysql",
            "host": "localhost",
            "port": 3306,
            "database": "dbname",
            "username": "user",
            "password": "pass",
            "charset": "utf8mb4",
            "collation": "utf8mb4_unicode_ci",
            "prefix": "",
            "options": {}
        },
        "sqlite": {
            "driver": "sqlite",
            "database": "database/database.sqlite"
        }
    }
}
```

Поле `default` указывает имя соединения, используемое по умолчанию. Каждое соединение описывается параметрами, специфичными для драйвера.

## Принципы проектирования

### Dependency Injection (DI)

Все зависимости (`DsnBuilder`, `QueryLoggerInterface`) передаются через конструктор `Database`. `DatabaseManager` принимает `ContainerInterface` и внедряет зависимости в создаваемые соединения. Это обеспечивает тестируемость и заменяемость компонентов.

### SOLID

- **Single Responsibility**: каждый класс имеет одну ответственность (например, `DsnBuilder` только строит DSN).
- **Open/Closed**: классы открыты для расширения (через интерфейсы) и закрыты для модификации.
- **Liskov Substitution**: `Database` может быть заменён любой реализацией `DatabaseInterface`.
- **Interface Segregation**: интерфейсы узконаправленные (`QueryLoggerInterface` содержит только метод логгирования).
- **Dependency Inversion**: высокоуровневые модули зависят от абстракций (`DatabaseInterface`, `QueryLoggerInterface`), а не от конкретных реализаций.

### DRY и KISS

Повторяющаяся логика построения DSN вынесена в `DsnBuilder`. Код методов `query`, `execute` максимально прост и не содержит избыточных проверок.

### PSR‑совместимость

- **PSR‑1, PSR‑12**: код соответствует стандартам оформления.
- **PSR‑4**: автозагрузка классов через Composer.
- **PSR‑11**: использование `ContainerInterface` для получения зависимостей.

## Расширение и кастомизация

### Добавление нового драйвера

1. Расширьте `DsnBuilder`, добавив поддержку нового драйвера в метод `build()`.
2. Убедитесь, что PDO поддерживает этот драйвер.
3. Конфигурация соединения должна содержать поле `driver` с именем нового драйвера.

### Собственный логгер запросов

Реализуйте `QueryLoggerInterface` и установите логгер через `DatabaseManager::setQueryLogger()`. Например:

```php
$logger = new class implements QueryLoggerInterface {
    public function logQuery(string $sql, float $duration, array $bindings = []): void {
        // запись в файл, отправка в Sentry и т.д.
    }
};
$databaseManager->setQueryLogger($logger);
```

### Интеграция с Debug‑сервисом

`DatabaseServiceProvider` автоматически настраивает логгирование запросов в Debug‑сервис, если тот доступен и имеет метод `logQuery`. Это позволяет видеть выполненные SQL‑запросы в панели отладки Architect.

## Ограничения

- Сервис не предоставляет ORM‑функциональности (нет маппинга объектов, отношений, репозиториев). Для этого предназначена Axiom ORM.
- Нет встроенной поддержки миграций, seed‑ов.
- Поддерживаются только реляционные БД, для которых существует PDO‑драйвер.

## Связь с Axiom ORM

Сервис Connect является низкоуровневой альтернативой Axiom ORM. Он может использоваться в проектах, где не требуется полноценная ORM, либо как основа для кастомных DAL. Axiom ORM может быть подключена параллельно, используя те же соединения PDO.

## Тестирование

Для тестирования сервиса можно использовать встроенные тесты (см. `test_config_loader.php`, `test_database_integration.php`). Рекомендуется использовать SQLite в памяти для юнит‑тестов.

## Заключение

Сервис Connect — это минималистичный, но полнофункциональный слой доступа к данным, соответствующий современным стандартам PHP‑разработки. Его архитектура позволяет легко интегрировать его в существующие проекты Architect Framework, расширять и заменять компоненты без изменения кода ядра.