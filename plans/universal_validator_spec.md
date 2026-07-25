# Техническая спецификация универсального валидатора

## Обзор
Универсальный валидатор - это компонент, который предоставляет гибкую систему проверки данных с поддержкой различных правил валидации, кастомных сообщений об ошибках и интеграцией с HTTP-запросами.

## Архитектура

### Основные классы

#### 1. Validator
Основной класс валидатора, который предоставляет API для валидации данных.

```php
<?php

namespace Architect\Validation;

class Validator
{
    /**
     * Валидирует данные с использованием заданных правил
     *
     * @param array $data Данные для валидации
     * @param array $rules Правила валидации
     * @param array $messages Кастомные сообщения об ошибках
     * @return Validator
     */
    public function make(array $data, array $rules, array $messages = []): self;
    
    /**
     * Проверяет, прошла ли валидация успешно
     *
     * @return bool
     */
    public function passes(): bool;
    
    /**
     * Проверяет, есть ли ошибки валидации
     *
     * @return bool
     */
    public function fails(): bool;
    
    /**
     * Получает ошибки валидации
     *
     * @return array
     */
    public function errors(): array;
    
    /**
     * Добавляет кастомное правило валидации
     *
     * @param string $name Название правила
     * @param callable $callback Функция валидации
     * @return self
     */
    public function extend(string $name, callable $callback): self;
}
```

#### 2. ValidationRuleInterface
Интерфейс для создания кастомных правил валидации.

```php
<?php

namespace Architect\Validation\Rules;

interface ValidationRuleInterface
{
    /**
     * Проверяет, соответствует ли значение правилу
     *
     * @param string $attribute Название атрибута
     * @param mixed $value Значение для проверки
     * @param array $parameters Параметры правила
     * @param Validator $validator Экземпляр валидатора
     * @return bool
     */
    public function passes(string $attribute, $value, array $parameters, Validator $validator): bool;
    
    /**
     * Получает сообщение об ошибке
     *
     * @param string $attribute Название атрибута
     * @return string
     */
    public function message(string $attribute): string;
}
```

#### 3. ValidationError
Класс для представления ошибок валидации.

```php
<?php

namespace Architect\Validation;

class ValidationError
{
    protected string $attribute;
    protected string $message;
    protected array $parameters;
    
    public function __construct(string $attribute, string $message, array $parameters = [])
    {
        $this->attribute = $attribute;
        $this->message = $message;
        $this->parameters = $parameters;
    }
    
    public function getAttribute(): string
    {
        return $this->attribute;
    }
    
    public function getMessage(): string
    {
        return $this->message;
    }
    
    public function getParameters(): array
    {
        return $this->parameters;
    }
}
```

## Встроенные правила валидации

1. **required** - Поле обязательно для заполнения
2. **email** - Проверяет, является ли значение корректным email адресом
3. **numeric** - Проверяет, является ли значение числом
4. **integer** - Проверяет, является ли значение целым числом
5. **string** - Проверяет, является ли значение строкой
6. **array** - Проверяет, является ли значение массивом
7. **min:length** - Проверяет минимальную длину строки или размер массива
8. **max:length** - Проверяет максимальную длину строки или размер массива
9. **size:value** - Проверяет точное значение или длину
10. **in:foo,bar,baz** - Проверяет, содержится ли значение в списке
11. **not_in:foo,bar,baz** - Проверяет, не содержится ли значение в списке
12. **unique:table,column,except,idColumn** - Проверяет уникальность значения в БД
13. **exists:table,column** - Проверяет существование значения в БД
14. **regex:pattern** - Проверяет значение по регулярному выражению
15. **date** - Проверяет, является ли значение датой
16. **before:date** - Проверяет, что дата меньше указанной
17. **after:date** - Проверяет, что дата больше указанной

## Интеграция с HTTP-запросами

### RequestValidatorTrait
Трейт для интеграции валидации с HTTP-запросами.

```php
<?php

namespace Architect\Http\Validation;

trait RequestValidatorTrait
{
    /**
     * Валидирует данные запроса
     *
     * @param array $rules Правила валидации
     * @param array $messages Кастомные сообщения
     * @return self
     * @throws ValidationException
     */
    public function validate(array $rules, array $messages = []): self;
    
    /**
     * Получает валидированные данные
     *
     * @return array
     */
    public function validated(): array;
}
```

## Конфигурация

Файл конфигурации `app/config/validation.json`:

```json
{
    "default_language": "ru",
    "messages": {
        "required": "Поле :attribute обязательно для заполнения.",
        "email": "Поле :attribute должно быть корректным email адресом.",
        "numeric": "Поле :attribute должно быть числом.",
        "min": {
            "numeric": "Поле :attribute должно быть не меньше :min.",
            "string": "Поле :attribute должно содержать не меньше :min символов.",
            "array": "Поле :attribute должно содержать не меньше :min элементов."
        },
        "max": {
            "numeric": "Поле :attribute должно быть не больше :max.",
            "string": "Поле :attribute должно содержать не больше :max символов.",
            "array": "Поле :attribute должно содержать не больше :max элементов."
        }
    }
}
```

## Использование

### Базовая валидация

```php
$validator = new Validator();
$validator->make($data, [
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:users',
    'age' => 'required|integer|min:18|max:100'
]);

if ($validator->fails()) {
    $errors = $validator->errors();
    // Обработка ошибок
}
```

### Интеграция с HTTP-запросами

```php
class UserController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed'
        ]);
        
        // Создание пользователя с валидированными данными
        User::create($validated);
    }
}
```

## Сервис-провайдер

```php
<?php

namespace Architect\Validation\Providers;

use Architect\Contracts\ServiceProviderInterface;
use Architect\Core\Contracts\ContainerInterface;

class ValidationServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerInterface $container): void
    {
        $container->singleton('validator', function ($container) {
            return new \Architect\Validation\Validator($container);
        });
    }
    
    public function boot(ContainerInterface $container): void
    {
        // Регистрация кастомных правил
    }
}
```

## Тестирование

### Unit-тесты
- Тестирование каждого правила валидации
- Тестирование кастомных правил
- Тестирование интеграции с HTTP-запросами
- Тестирование сообщений об ошибках

### Интеграционные тесты
- Тестирование валидации с реальными данными
- Тестирование уникальности и существования в БД
- Тестирование вложенных данных и массивов

## Производительность

### Оптимизации
- Кэширование скомпилированных правил
- Ленивая загрузка сообщений об ошибках
- Минимизация количества вызовов БД для правил unique/exists

## Совместимость

### Существующая система
- Интеграция с существующими HTTP-запросами
- Совместимость с Axiom ORM для правил unique/exists
- Интеграция с системой локализации для сообщений об ошибках

### Обратная совместимость
- Поддержка старого FormValidator (для постепенной миграции)
- Совместимость с существующими формами