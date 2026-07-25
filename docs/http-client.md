# HTTP-клиент

Architect HTTP-клиент – это официальный PSR-18 совместимый клиент для отправки HTTP-запросов, разработанный специально для Architect framework. Он предоставляет мощные возможности для работы с внешними API, веб-сервисами и микросервисами, поддерживает синхронные и асинхронные запросы, цепочки middleware и конфигурируемые драйверы.

## Оглавление

- [Установка](#установка)
- [Конфигурация](#конфигурация)
- [Базовое использование](#базовое-использование)
- [Интеграция с DI](#интеграция-с-di)
- [Асинхронные запросы](#асинхронные-запросы)
- [Middleware](#middleware)
- [Драйверы](#драйверы)
- [Исключения](#исключения)
- [Примеры](#примеры)
- [Часто задаваемые вопросы](#часто-задаваемые-вопросы)

## Установка

Установите пакет через Composer:

```bash
composer require architect/http-client
```

Пакет автоматически регистрирует сервис `http_client` в контейнере зависимостей через `HttpClientServiceProvider`. Для ручной настройки вы можете изменить конфигурацию.

## Конфигурация

По умолчанию конфигурация находится в `app/config/http-client.php`. Если файл отсутствует, используется конфигурация по умолчанию. Вы можете создать файл со следующим содержимым:

```php
<?php

return [
    'default_driver' => 'curl', // curl, stream, curl_multi
    'drivers' => [
        'curl' => [
            'timeout' => 30,
            'connect_timeout' => 5,
            'verify' => true,
            'proxy' => null,
        ],
        'stream' => [
            'timeout' => 30,
            'ssl_verify_peer' => true,
            'follow_location' => true,
        ],
        'curl_multi' => [
            'concurrency' => 10,
            'timeout' => 30,
            'connect_timeout' => 5,
        ],
    ],
    'middleware' => [
        \Architect\HttpClient\Middleware\LoggingMiddleware::class,
    ],
];
```

### Параметры драйверов

- **curl** – использует расширение cURL. Поддерживает все стандартные опции cURL.
- **stream** – использует PHP-потоки (stream wrappers). Работает без cURL, но имеет ограниченную функциональность.
- **curl_multi** – использует cURL multi для параллельных запросов. Параметр `concurrency` определяет максимальное количество одновременных соединений.

## Базовое использование

### Создание клиента вручную

```php
use Architect\HttpClient\DriverFactory;
use Architect\HttpClient\HttpClient;
use Nyholm\Psr7\Request;

$driverFactory = new DriverFactory();
$driver = $driverFactory->create('curl'); // или 'stream', 'curl_multi'
$client = new HttpClient($driver);

$request = new Request('GET', 'https://httpbin.org/get');
$response = $client->sendRequest($request);

echo $response->getStatusCode(); // 200
echo $response->getBody();
```

### Упрощённые методы

`HttpClient` предоставляет удобные методы для распространённых HTTP-методов:

```php
$response = $client->get('https://httpbin.org/get');
$response = $client->post('https://httpbin.org/post', ['body' => 'data']);
$response = $client->put('https://httpbin.org/put', ['body' => 'data']);
$response = $client->patch('https://httpbin.org/patch', ['body' => 'data']);
$response = $client->delete('https://httpbin.org/delete');
```

Эти методы автоматически создают PSR-7 запросы и возвращают PSR-7 ответы.

## Интеграция с DI

Пакет включает `HttpClientServiceProvider`, который регистрирует сервис `http_client` в контейнере зависимостей Architect. Сервис реализует `Psr\Http\Client\ClientInterface` и `Architect\HttpClient\Contracts\HttpClientInterface`.

### Использование в контроллерах

Внедрите зависимость через конструктор:

```php
use Psr\Http\Client\ClientInterface;

class ApiController extends Controller
{
    public function __construct(
        protected ClientInterface $httpClient
    ) {}

    public function index()
    {
        $response = $this->httpClient->get('https://api.example.com/data');
        $data = json_decode($response->getBody(), true);
        return $this->json($data);
    }
}
```

### Использование в сервисах

Вы можете запросить `http_client` из контейнера вручную:

```php
$client = $container->get('http_client');
```

## Асинхронные запросы

Для параллельной отправки нескольких запросов используйте драйвер `curl_multi` и интерфейс `PromiseInterface`.

### Отправка асинхронных запросов

```php
use Architect\HttpClient\Contracts\PromiseInterface;

$promises = [];
$promises[] = $client->sendAsyncRequest($request1);
$promises[] = $client->sendAsyncRequest($request2);
$promises[] = $client->sendAsyncRequest($request3);

// Ожидание завершения всех запросов
foreach ($promises as $promise) {
    $response = $promise->wait();
    // обработка ответа
}
```

### Метод `pool()`

Для удобной групповой обработки URL-адресов используйте метод `pool()`:

```php
$responses = $client->pool([
    'https://api.example.com/users/1',
    'https://api.example.com/users/2',
    'https://api.example.com/users/3',
]);

foreach ($responses as $response) {
    echo $response->getStatusCode();
}
```

Метод `pool()` автоматически создаёт GET-запросы и возвращает массив ответов в том же порядке.

## Middleware

Middleware позволяют модифицировать запросы и ответы, добавлять логирование, кэширование, аутентификацию и т.д.

### Встроенное middleware

- **LoggingMiddleware** – логирует запросы и ответы с использованием PSR-3 логгера.

### Добавление middleware

```php
use Architect\HttpClient\Middleware\LoggingMiddleware;
use Psr\Log\NullLogger;

$logger = new NullLogger();
$loggingMiddleware = new LoggingMiddleware($logger);
$client = $client->withMiddleware($loggingMiddleware);
```

Middleware применяются в порядке добавления.

### Создание собственного middleware

Создайте класс, реализующий `Architect\HttpClient\Contracts\MiddlewareInterface`:

```php
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class CustomMiddleware implements MiddlewareInterface
{
    public function process(RequestInterface $request, callable $next): ResponseInterface
    {
        // Модификация запроса
        $request = $request->withHeader('X-Custom-Header', 'value');

        // Передача запроса следующему middleware/драйверу
        $response = $next($request);

        // Модификация ответа
        $response = $response->withHeader('X-Processed-By', 'CustomMiddleware');

        return $response;
    }
}
```

Зарегистрируйте middleware в конфигурации или добавьте через `withMiddleware()`.

## Драйверы

### Выбор драйвера

Драйвер определяет, как отправляются HTTP-запросы. Доступны три встроенных драйвера:

1. **CurlDriver** – использует расширение cURL. Рекомендуется для production.
2. **StreamDriver** – использует PHP-потоки. Подходит для окружений без cURL.
3. **CurlMultiDriver** – использует cURL multi для параллельных запросов.

Вы можете указать драйвер по умолчанию в конфигурации или создать конкретный драйвер через `DriverFactory`.

### Создание собственного драйвера

Реализуйте `Architect\HttpClient\Contracts\DriverInterface`:

```php
use Architect\HttpClient\Contracts\DriverInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class CustomDriver implements DriverInterface
{
    public function send(RequestInterface $request): ResponseInterface
    {
        // Ваша реализация отправки запроса
    }

    public function sendAsync(RequestInterface $request): PromiseInterface
    {
        // Возвращайте promise для асинхронной отправки
    }
}
```

Зарегистрируйте драйвер в конфигурации:

```php
'drivers' => [
    'custom' => [
        'class' => \App\Drivers\CustomDriver::class,
        'options' => [...],
    ],
],
```

## Исключения

Пакет выбрасывает следующие исключения:

- `Architect\HttpClient\Exception\HttpClientException` – общее исключение клиента.
- `Architect\HttpClient\Exception\NetworkException` – ошибка сети (невозможно отправить запрос).
- `Architect\HttpClient\Exception\RequestException` – ошибка запроса (неверный URI, заголовки и т.д.).

Все исключения реализуют `Psr\Http\Client\ClientExceptionInterface`.

Пример обработки:

```php
try {
    $response = $client->sendRequest($request);
} catch (\Architect\HttpClient\Exception\NetworkException $e) {
    // Обработка ошибки сети
} catch (\Psr\Http\Client\ClientExceptionInterface $e) {
    // Обработка других ошибок клиента
}
```

## Примеры

### Полный пример интеграции с Architect

```php
// В контроллере
public function fetchUserData(int $userId)
{
    $response = $this->http_client->get("https://api.example.com/users/{$userId}");
    if ($response->getStatusCode() === 200) {
        $user = json_decode($response->getBody(), true);
        return $this->view('user', ['user' => $user]);
    }
    throw new \Exception('User not found');
}
```

### Пример с middleware логирования

```php
use Architect\HttpClient\Middleware\LoggingMiddleware;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('http');
$logger->pushHandler(new StreamHandler('logs/http.log'));
$middleware = new LoggingMiddleware($logger);

$client = $client->withMiddleware($middleware);
$client->get('https://httpbin.org/ip'); // запрос будет залогирован
```

### Параллельные запросы с curl_multi

```php
$driverFactory = new DriverFactory();
$driver = $driverFactory->create('curl_multi');
$client = new HttpClient($driver);

$urls = [
    'https://httpbin.org/delay/1',
    'https://httpbin.org/delay/2',
    'https://httpbin.org/delay/1',
];

$start = microtime(true);
$responses = $client->pool($urls);
$time = microtime(true) - $start;

echo "Запросы выполнены за {$time} секунд"; // ~2 секунды вместо 4
```

## Часто задаваемые вопросы

### Как изменить таймаут для конкретного запроса?

Создайте запрос с кастомными заголовками или опциями через middleware. Либо создайте отдельный драйвер с нужными настройками.

### Поддерживает ли клиент HTTP/2?

Да, если используется драйвер cURL и сервер поддерживает HTTP/2.

### Можно ли использовать клиент вне Architect framework?

Да, пакет является самостоятельным и может быть использован в любом PHP-проекте с поддержкой PSR-7 и PSR-18.

### Как отключить проверку SSL?

В конфигурации драйвера `curl` установите `'verify' => false`. Не рекомендуется для production.

### Где найти больше примеров?

Смотрите папку `examples/` в пакете `architect/http-client`.

---

## Ссылки

- [PSR-7: HTTP message interfaces](https://www.php-fig.org/psr/psr-7/)
- [PSR-18: HTTP client](https://www.php-fig.org/psr/psr-18/)
- [Документация Architect framework](../README.md)
- [Исходный код HTTP-клиента](https://github.com/architect-framework/http-client)