# Стратегия тестирования новых компонентов

## Обзор
Этот документ описывает стратегию тестирования для новых компонентов Architect Framework 2: универсального валидатора, менеджера сессий, системы пагинации, файлового хранилища и системы отправки почты.

## Общие принципы тестирования

### Пирамида тестирования
1. **Unit-тесты** (70%) - Тестирование отдельных функций и классов
2. **Интеграционные тесты** (25%) - Тестирование взаимодействия компонентов
3. **End-to-End тесты** (5%) - Тестирование полных сценариев использования

### Покрытие кода
- Целевое покрытие кода: 80% для критических компонентов
- 100% покрытие для основных путей выполнения
- Тестирование граничных условий и ошибок

### Автоматизация
- Все тесты должны быть автоматизированы
- Тесты должны запускаться при каждом изменении кода
- Интеграция с CI/CD pipeline

## Unit-тестирование

### Общие подходы
- Тестирование каждого метода класса отдельно
- Использование моков для внешних зависимостей
- Тестирование всех возможных путей выполнения
- Тестирование граничных условий

### Валидатор
```php
// Тестирование правил валидации
public function testRequiredRule()
{
    $validator = new Validator();
    $validator->make(['name' => ''], ['name' => 'required']);
    $this->assertTrue($validator->fails());
    
    $validator->make(['name' => 'John'], ['name' => 'required']);
    $this->assertTrue($validator->passes());
}

// Тестирование кастомных правил
public function testCustomRule()
{
    $validator = new Validator();
    $validator->extend('custom', function ($attribute, $value) {
        return strlen($value) > 5;
    });
    
    $validator->make(['name' => 'John'], ['name' => 'custom']);
    $this->assertTrue($validator->fails());
}
```

### Менеджер сессий
```php
// Тестирование драйверов сессий
public function testFileSessionDriver()
{
    $handler = new FileSessionHandler('/tmp');
    $session = new Store('test', $handler);
    
    $session->set('key', 'value');
    $this->assertEquals('value', $session->get('key'));
}

// Тестирование flash-данных
public function testFlashData()
{
    $session = new Store('test', new ArraySessionHandler());
    $session->flash('status', 'success');
    $this->assertEquals('success', $session->get('status'));
    $this->assertNull($session->get('status')); // После получения flash-данные удаляются
}
```

### Пагинация
```php
// Тестирование пагинатора
public function testPaginator()
{
    $items = range(1, 10);
    $paginator = new LengthAwarePaginator($items, 100, 10, 1);
    
    $this->assertEquals(10, $paginator->total());
    $this->assertEquals(1, $paginator->currentPage());
    $this->assertEquals(10, $paginator->lastPage());
}

// Тестирование URL
public function testUrlGeneration()
{
    $paginator = new LengthAwarePaginator([], 100, 10, 1, [
        'path' => 'http://example.com/users'
    ]);
    
    $this->assertEquals('http://example.com/users?page=2', $paginator->url(2));
}
```

### Файловое хранилище
```php
// Тестирование локального драйвера
public function testLocalFilesystem()
{
    $filesystem = new LocalFilesystem('/tmp');
    
    $filesystem->put('test.txt', 'content');
    $this->assertEquals('content', $filesystem->get('test.txt'));
    $this->assertTrue($filesystem->exists('test.txt'));
    
    $filesystem->delete('test.txt');
    $this->assertFalse($filesystem->exists('test.txt'));
}

// Тестирование загрузки файлов
public function testUploadedFile()
{
    $file = new UploadedFile('/tmp/test.txt', 'original.txt', 'text/plain');
    
    $this->assertEquals('original.txt', $file->getClientOriginalName());
    $this->assertEquals('text/plain', $file->getClientMimeType());
    $this->assertTrue($file->isValid());
}
```

### Почта
```php
// Тестирование транспорта массива
public function testArrayTransport()
{
    $transport = new ArrayTransport();
    $message = new Message(new Swift_Message());
    $message->to('test@example.com')->subject('Test');
    
    $transport->send($message);
    $this->assertCount(1, $transport->getMessages());
}

// Тестирование Mailable
public function testMailable()
{
    $mailable = new TestMailable();
    $this->assertInstanceOf(Mailable::class, $mailable);
    
    // Тестирование методов построения
    $mailable->subject('Test Subject');
    $mailable->to('test@example.com');
    // ...
}
```

## Интеграционное тестирование

### Валидация с HTTP-запросами
```php
// Тестирование интеграции с Request
public function testRequestValidation()
{
    $request = new Request(['name' => '']);
    $request->validate(['name' => 'required']);
    
    // Ожидаем исключение ValidationException
    $this->expectException(ValidationException::class);
    
    $request->validate(['name' => 'required']);
}
```

### Сессии с HTTP-запросами
```php
// Тестирование интеграции с Request
public function testSessionIntegration()
{
    $session = new Store('test', new ArraySessionHandler());
    $request = new Request();
    $request->setSession($session);
    
    $request->session()->put('key', 'value');
    $this->assertEquals('value', $request->session()->get('key'));
}
```

### Пагинация с Axiom ORM
```php
// Тестирование интеграции с QueryBuilder
public function testPaginationWithQueryBuilder()
{
    // Создание тестовой базы данных
    $connection = new Connection(['driver' => 'sqlite', 'database' => ':memory:']);
    
    // Создание таблицы и данных
    // ...
    
    // Тестирование пагинации
    $paginator = User::paginate(5);
    $this->assertInstanceOf(LengthAwarePaginator::class, $paginator);
    $this->assertCount(5, $paginator->items());
}
```

### Файловое хранилище с HTTP-загрузками
```php
// Тестирование загрузки файлов
public function testFileUpload()
{
    $uploadedFile = new UploadedFile(
        '/tmp/test.txt',
        'original.txt',
        'text/plain',
        UPLOAD_ERR_OK,
        true
    );
    
    $path = $uploadedFile->store('uploads');
    $this->assertStringStartsWith('uploads/', $path);
}
```

### Почта с очередями
```php
// Тестирование интеграции с очередями
public function testMailQueue()
{
    $queue = new ArrayQueue();
    $mailer = new Mailer(new ArrayTransport(), $this->getViewFactory(), $queue);
    
    $mailer->to('test@example.com')->queue(new TestMailable());
    
    $this->assertCount(1, $queue->getJobs());
}
```

## End-to-End тестирование

### Полные сценарии использования
```php
// Тестирование регистрации пользователя с валидацией и отправкой письма
public function testUserRegistration()
{
    // 1. Отправка формы регистрации
    $response = $this->post('/register', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123'
    ]);
    
    // 2. Проверка валидации
    $response->assertStatus(302);
    
    // 3. Проверка создания пользователя
    $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
    
    // 4. Проверка отправки письма
    // (проверка через ArrayTransport)
}
```

### Тестирование файловых операций
```php
// Тестирование загрузки и обработки файлов
public function testFileUploadAndProcessing()
{
    // 1. Загрузка файла
    $response = $this->post('/upload', [
        'file' => UploadedFile::fake()->image('avatar.jpg')
    ]);
    
    // 2. Проверка сохранения
    $response->assertStatus(200);
    
    // 3. Проверка наличия файла
    // $this->assertTrue(Storage::disk('public')->exists('avatars/avatar.jpg'));
}
```

## Тестирование производительности

### Бенчмарки
```php
// Тестирование производительности валидации
public function testValidationPerformance()
{
    $data = [];
    $rules = [];
    
    // Создание большого набора данных
    for ($i = 0; $i < 1000; $i++) {
        $data["field_{$i}"] = "value_{$i}";
        $rules["field_{$i}"] = 'required|string|max:255';
    }
    
    $start = microtime(true);
    $validator = new Validator();
    $validator->make($data, $rules);
    $validator->passes();
    $end = microtime(true);
    
    // Проверка, что валидация занимает менее 100ms
    $this->assertLessThan(0.1, $end - $start);
}
```

### Нагрузочное тестирование
```php
// Тестирование под высокой нагрузкой
public function testHighLoad()
{
    // Создание пула потоков для одновременной валидации
    $threads = [];
    $results = [];
    
    for ($i = 0; $i < 100; $i++) {
        $threads[] = new Thread(function () use (&$results, $i) {
            $validator = new Validator();
            $validator->make(
                ['email' => "user{$i}@example.com"],
                ['email' => 'required|email']
            );
            $results[] = $validator->passes();
        });
    }
    
    // Запуск всех потоков
    foreach ($threads as $thread) {
        $thread->start();
    }
    
    // Ожидание завершения
    foreach ($threads as $thread) {
        $thread->join();
    }
    
    // Проверка результатов
    $this->assertCount(100, $results);
    $this->assertContainsOnly(true, $results);
}
```

## Тестирование безопасности

### Валидация входных данных
```php
// Тестирование защиты от XSS
public function testXssProtection()
{
    $validator = new Validator();
    $validator->make(['name' => '<script>alert("xss")</script>'], ['name' => 'string']);
    
    // Проверка, что валидация проходит, но данные должны быть экранированы при выводе
    $this->assertTrue($validator->passes());
}

// Тестирование SQL-инъекций
public function testSqlInjection()
{
    $validator = new Validator();
    $validator->make(['email' => "test'; DROP TABLE users; --"], ['email' => 'email']);
    
    // Проверка валидации email
    $this->assertTrue($validator->fails());
}
```

### Защита файлов
```php
// Тестирование загрузки вредоносных файлов
public function testMaliciousFileUpload()
{
    $this->expectException(ValidationException::class);
    
    // Попытка загрузки PHP файла
    $file = UploadedFile::fake()->create('malicious.php', 100, 'application/x-php');
    $file->store('uploads');
}
```

## Тестирование совместимости

### Обратная совместимость
```php
// Тестирование совместимости с существующими API
public function testBackwardCompatibility()
{
    // Проверка, что старые методы все еще работают
    $this->assertTrue(method_exists(Validator::class, 'make'));
    $this->assertTrue(method_exists(Session::class, 'get'));
    // ...
}
```

### Кросс-платформенное тестирование
```php
// Тестирование на разных операционных системах
public function testCrossPlatform()
{
    // Проверка работы с различными путями файловой системы
    if (PHP_OS_FAMILY === 'Windows') {
        $this->assertStringContainsString('\\', DIRECTORY_SEPARATOR);
    } else {
        $this->assertEquals('/', DIRECTORY_SEPARATOR);
    }
}
```

## Инструменты тестирования

### PHPUnit
- Основной фреймворк для unit-тестирования
- Конфигурация в `phpunit.xml`
- Поддержка data providers и mocking

### Инструменты покрытия кода
- Xdebug для измерения покрытия кода
- Генерация отчетов о покрытии
- Интеграция с CI/CD

### Инструменты профилирования
- Blackfire для профилирования производительности
- XHProf для анализа производительности
- Инструменты мониторинга памяти

## Автоматизация тестирования

### CI/CD интеграция
```yaml
# .github/workflows/test.yml
name: Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
      - name: Install dependencies
        run: composer install
      - name: Run tests
        run: vendor/bin/phpunit
      - name: Check code coverage
        run: vendor/bin/phpunit --coverage-clover=coverage.xml
```

### Предварительные коммиты
```bash
# pre-commit hook
#!/bin/bash
vendor/bin/phpunit --stop-on-failure
if [ $? -ne 0 ]; then
    echo "Tests failed. Commit aborted."
    exit 1
fi
```

## Отчетность по тестированию

### Метрики качества
- Покрытие кода (%)
- Количество тестов
- Время выполнения тестов
- Количество ошибок
- Процент успешных сборок

### Дашборды
- Интеграция с системами мониторинга
- Визуализация метрик тестирования
- Уведомления о регрессиях

## План тестирования

### Фаза 1: Unit-тестирование (2 недели)
- Разработка unit-тестов для всех компонентов
- Достижение 80% покрытия кода
- Исправление найденных ошибок

### Фаза 2: Интеграционное тестирование (1 неделя)
- Разработка интеграционных тестов
- Тестирование взаимодействия компонентов
- Исправление проблем интеграции

### Фаза 3: End-to-End тестирование (1 неделя)
- Разработка сценариев end-to-end тестирования
- Тестирование полных пользовательских сценариев
- Оптимизация производительности

### Фаза 4: Непрерывное тестирование (постоянно)
- Интеграция с CI/CD
- Мониторинг качества кода
- Регулярное обновление тестов