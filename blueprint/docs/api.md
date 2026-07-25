# API Reference

Полный справочник по API Blueprint.

---

## Blueprint

Главный класс шаблонизатора.

### Конструктор

```php
public function __construct(
    array|BlueprintConfig $config = [],
    ?object $container = null
)
```

### Методы рендеринга

```php
// Рендеринг файла шаблона
public function render(string $template, array $context = []): string

// Рендеринг строки
public function renderString(string $source, array $context = []): string

// Проверка существования шаблона
public function exists(string $template): bool
```

### Методы компиляции

```php
// Компиляция файла шаблона
public function compile(string $template): string

// Компиляция строки
public function compileString(string $source): string
```

### Конфигурация

```php
// Добавить путь к шаблонам
public function addPath(string $path): void

// Установить пути к шаблонам
public function setPaths(array $paths): void

// Получить пути
public function getPaths(): array

// Добавить расширение файла
public function addExtension(string $extension): void
```

### Глобальные переменные

```php
// Добавить глобальную переменную
public function addGlobal(string $key, mixed $value): void

// Добавить несколько переменных
public function addGlobals(array $globals): void

// Получить все глобальные переменные
public function getGlobals(): array

// Очистить глобальные переменные
public function clearGlobals(): void
```

### Регистрация расширений

```php
// Зарегистрировать фильтр
public function registerFilter(string $name, callable $filter): void

// Зарегистрировать функцию
public function registerFunction(string $name, callable $function): void

// Зарегистрировать расширение
public function addExtension(BlueprintExtension $extension): void
```

### DI-контейнер

```php
// Установить контейнер
public function setContainer(object $container): void

// Получить контейнер
public function getContainer(): ?object
```

### Кеш

```php
// Очистить кеш
public function clearCache(): bool

// Очистить кеш конкретного шаблона
public function clearCacheFor(string $template): bool
```

---

## BlueprintConfig

Конфигурация шаблонизатора.

### Конструктор

```php
public function __construct(array $config = [])
```

### Параметры

| Параметр | Тип | По умолчанию | Описание |
|----------|-----|--------------|----------|
| `debug` | bool | `false` | Режим отладки |
| `show_errors` | bool | `true` | Показывать ошибки |
| `cache_enabled` | bool | `false` | Включить кеш |
| `cache_path` | string | `null` | Путь к кешу |
| `paths` | array | `[]` | Пути к шаблонам |
| `extensions` | array | `['.blade.php']` | Расширения файлов |
| `autoescape` | bool | `true` | Авто-экранирование |
| `strict_variables` | bool | `false` | Строгий режим |

### Методы

```php
public function get(string $key, mixed $default = null): mixed
public function set(string $key, mixed $value): void
public function all(): array
```

---

## RuntimeInterface

Интерфейс для Runtime.

```php
interface RuntimeInterface
{
    public function escape(mixed $value): string;
    public function raw(mixed $value): string;
    public function applyFilter(string $name, mixed $value, array $args = []): mixed;
    public function callFunction(string $name, array $args = [], array $context = []): mixed;
    public function getProperty(mixed $object, string $property): mixed;
    public function callMethod(mixed $object, string $method, array $args = []): mixed;
}
```

---

## FilterRegistryInterface

Интерфейс для реестра фильтров.

```php
interface FilterRegistryInterface
{
    public function register(string $name, callable $filter): void;
    public function has(string $name): bool;
    public function get(string $name): ?callable;
    public function all(): array;
}
```

---

## FunctionRegistryInterface

Интерфейс для реестра функций.

```php
interface FunctionRegistryInterface
{
    public function register(string $name, callable $function): void;
    public function has(string $name): bool;
    public function get(string $name): ?callable;
    public function all(): array;
}
```

---

## Lexer

Лексический анализатор.

```php
namespace Blueprint\Engine\Lexer;

class Lexer
{
    public function tokenize(string $source): TokenStream;
    public function tokenizeExpression(string $expression): TokenStream;
}
```

### Token

```php
class Token
{
    public readonly string $type;
    public readonly string $value;
    public readonly int $line;
    public readonly int $column;
    
    public function is(string $type, ?string $value = null): bool;
    public function isOneOf(string ...$types): bool;
    public static function eof(int $line = 0, int $column = 0): self;
}
```

### TokenTypes

```php
class TokenTypes
{
    public const EOF = 'EOF';
    public const TEXT = 'TEXT';
    public const VAR_START = 'VAR_START';    // {{
    public const VAR_END = 'VAR_END';        // }}
    public const BLOCK_START = 'BLOCK_START'; // {%
    public const BLOCK_END = 'BLOCK_END';    // %}
    public const RAW_START = 'RAW_START';    // {!!
    public const RAW_END = 'RAW_END';        // !!}
    public const NAME = 'NAME';
    public const NUMBER = 'NUMBER';
    public const STRING = 'STRING';
    public const OPERATOR = 'OPERATOR';
    public const PUNCTUATION = 'PUNCTUATION';
}
```

---

## Parser

Синтаксический анализатор.

```php
namespace Blueprint\Engine\Parser;

class Parser
{
    public function parse(array $tokens): array;
    public function parseExpression(TokenStream $stream): array;
    public function parseStatement(TokenStream $stream): array;
}
```

### AST Node Types

| Тип | Описание |
|-----|----------|
| `body` | Корневой узел |
| `text` | Текст |
| `print` | Вывод выражения |
| `variable` | Переменная |
| `property` | Доступ к свойству |
| `method` | Вызов метода |
| `filter` | Применение фильтра |
| `function` | Вызов функции |
| `binary` | Бинарная операция |
| `ternary` | Тернарная операция |
| `if` | Условие |
| `for` | Цикл for |
| `foreach` | Цикл foreach |
| `block` | Блок |
| `extends` | Наследование |
| `include` | Включение |
| `set` | Присваивание |
| `macro` | Макрос |

---

## Compiler

Компилятор в PHP.

```php
namespace Blueprint\Engine\Compiler;

class Compiler
{
    public function compile(string $source): string;
    public function compileAST(array $ast): string;
}
```

---

## Cache

Управление кешем.

```php
namespace Blueprint\Engine\Cache;

class CacheManager
{
    public function has(string $key): bool;
    public function get(string $key): ?string;
    public function set(string $key, string $content): bool;
    public function delete(string $key): bool;
    public function clear(): bool;
}
```

---

## Exceptions

```php
namespace Blueprint\Engine\Exception;

class BlueprintException extends \Exception
{
    public static function templateNotFound(string $template): self;
    public static function syntaxError(string $message, int $line = 0): self;
    public static function filterNotFound(string $name): self;
    public static function functionNotFound(string $name): self;
}
```

---

## События

Blueprint не использует события. Для расширения используйте:

- `registerFilter()` — добавление фильтров
- `registerFunction()` — добавление функций
- `addExtension()` — добавление расширений
- `addGlobal()` — добавление глобальных переменных
