# Architect HTTP Client

Full-featured HTTP client for Architect Framework with PSR-18 support, async requests, middleware, and multiple drivers.

## Features

- **PSR-18 & PSR-7 compliant** – Interoperable with any PSR-7/PSR-18 ecosystem.
- **Multiple drivers** – Choose between cURL, Stream, Guzzle, or Symfony HttpClient (adapters).
- **Middleware pipeline** – Easily extend with custom middleware (logging, retry, authentication, etc.).
- **Async requests** – Promise-based asynchronous HTTP (supported by cURL multi, Guzzle, Symfony).
- **DI integration** – Seamlessly integrates with Architect Framework's service container.
- **Configuration driven** – Configure drivers, middleware, and default options via config files.
- **SOLID & DRY** – Clean architecture following best practices.

## Installation

Add the package to your Architect project:

```bash
composer require architect/http-client
```

If you're developing within the Architect framework repository, the package is already included as a path repository.

## Basic Usage

### Using the HTTP Client directly

```php
use Architect\HttpClient\DriverFactory;
use Architect\HttpClient\HttpClient;
use Nyholm\Psr7\Request;

$driverFactory = new DriverFactory();
$driver = $driverFactory->create('curl'); // or 'stream'
$client = new HttpClient($driver);

$request = new Request('GET', 'https://httpbin.org/get');
$response = $client->sendRequest($request);

echo $response->getStatusCode();
echo $response->getBody();
```

### Using the service container (Architect Framework)

If you're using Architect Framework, the client is automatically registered as a service.

```php
// In a controller or service
$client = $container->get('http.client');
// or via dependency injection
public function __construct(\Architect\HttpClient\Contracts\HttpClientInterface $client)
{
    $this->client = $client;
}

// Send a request
$response = $client->sendRequest($request);
```

### Using the facade (if configured)

```php
use Architect\HttpClient\Facades\Http;

$response = Http::get('https://httpbin.org/get');
```

## Configuration

The package comes with a default configuration file located at `config/http-client.php`. You can publish it to your app config directory (if using Architect) or modify it directly.

Key configuration options:

- `default_driver` – Driver to use (`curl`, `stream`, `guzzle`, `symfony`).
- `drivers` – Driver-specific options (timeouts, SSL verification, etc.).
- `middlewares` – List of middleware classes to apply globally.
- `options` – Global request options (base URI, default headers).

Example configuration:

```php
return [
    'default_driver' => 'curl',
    'drivers' => [
        'curl' => [
            'options' => [
                'timeout' => 30,
                'verify_ssl' => true,
            ],
        ],
    ],
    'middlewares' => [
        \Architect\HttpClient\Middleware\LoggingMiddleware::class,
        \Architect\HttpClient\Middleware\RetryMiddleware::class,
    ],
    'options' => [
        'base_uri' => 'https://api.example.com/v1/',
        'headers' => [
            'User-Agent' => 'MyApp/1.0',
        ],
    ],
];
```

## Middleware

Middleware allows you to intercept requests and responses. The package includes a few built-in middleware:

- `LoggingMiddleware` – Logs requests and responses using a PSR-3 logger.
- `RetryMiddleware` – Retries failed requests with exponential backoff.
- `AuthMiddleware` – Adds authentication headers (Bearer, Basic, etc.).

### Creating custom middleware

Implement `Architect\HttpClient\Contracts\MiddlewareInterface`:

```php
use Architect\HttpClient\Contracts\MiddlewareInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class CustomMiddleware implements MiddlewareInterface
{
    public function process(RequestInterface $request, callable $next): ResponseInterface
    {
        // Modify request before sending
        $request = $request->withHeader('X-Custom', 'value');
        
        $response = $next($request);
        
        // Modify response after receiving
        return $response->withHeader('X-Processed-By', 'my-middleware');
    }
}
```

Then add your middleware to the configuration or attach it dynamically:

```php
$client = $client->withMiddleware(new CustomMiddleware());
```

## Async Requests

For asynchronous HTTP requests, use the `sendAsync` method which returns a `PromiseInterface`.

```php
$promise = $client->sendAsync($request);

$promise->then(
    function ($response) {
        echo 'Success: ' . $response->getStatusCode();
    },
    function ($exception) {
        echo 'Error: ' . $exception->getMessage();
    }
);

// Wait for the promise to complete (blocking)
$response = $promise->wait();
```

## Drivers

### cURL Driver (`curl`)

Uses the PHP cURL extension. Supports synchronous and asynchronous requests (via `curl_multi`). Recommended for production.

### Stream Driver (`stream`)

Uses PHP's stream wrappers. Works without cURL but with fewer features. Suitable for environments where cURL is unavailable.

### Guzzle Driver (`guzzle`)

Adapter for Guzzle HTTP Client. Requires `guzzlehttp/guzzle` package. Provides full Guzzle functionality.

### Symfony Driver (`symfony`)

Adapter for Symfony HttpClient. Requires `symfony/http-client` package.

## Exception Handling

The client throws exceptions implementing PSR-18's `ClientExceptionInterface`:

- `HttpClientException` – Base exception.
- `NetworkException` – Network‑level errors (timeout, connection refused).
- `RequestException` – Invalid request or 4xx/5xx responses.

```php
try {
    $response = $client->sendRequest($request);
} catch (\Architect\HttpClient\Exception\NetworkException $e) {
    // Handle network issues
} catch (\Architect\HttpClient\Exception\RequestException $e) {
    // Handle HTTP errors
}
```

## Testing

Run the test suite with PHPUnit:

```bash
cd architect/http-client
composer install
vendor/bin/phpunit
```

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## License

MIT License. See [LICENSE](LICENSE) file.