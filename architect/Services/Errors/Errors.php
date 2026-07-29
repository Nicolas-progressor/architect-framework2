<?php

declare(strict_types=1);

namespace Architect\Services\Errors;

use Architect\Contracts\Core\ContainerInterface;
use Architect\Core\EnvironmentManager;
use Architect\Services\Config\Contracts\ConfigInterface;
use Architect\Services\Errors\Contracts\ErrorHandlerInterface;
use Architect\Services\Errors\Contracts\ErrorRendererInterface;
use Architect\Services\Template\Contracts\TemplateInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Errors service for handling errors and exceptions.
 * Full context error page with request, route, queries info.
 *
 * Implements PSR-3 for logging and uses proper DI.
 */
class Errors implements ErrorHandlerInterface, ErrorRendererInterface
{
    /** @var bool Is initialized */
    private bool $initialized = false;

    /** @var ContainerInterface|null */
    private ?ContainerInterface $container = null;

    /** @var string Framework version */
    private const FRAMEWORK_VERSION = '2.0.0';

    /**
     * Fatal error types that should trigger shutdown handler.
     */
    private const FATAL_ERRORS = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];

    /**
     * Map of PHP error codes to error type names.
     */
    private const ERROR_TYPES = [
        E_ERROR             => 'Fatal Error',
        E_WARNING           => 'Warning',
        E_PARSE             => 'Parse Error',
        E_NOTICE            => 'Notice',
        E_CORE_ERROR        => 'Core Error',
        E_CORE_WARNING      => 'Core Warning',
        E_COMPILE_ERROR     => 'Compile Error',
        E_COMPILE_WARNING   => 'Compile Warning',
        E_USER_ERROR        => 'User Error',
        E_USER_WARNING      => 'User Warning',
        E_USER_NOTICE       => 'User Notice',
        E_RECOVERABLE_ERROR => 'Recoverable Error',
        E_DEPRECATED        => 'Deprecated',
        E_USER_DEPRECATED   => 'User Deprecated',
    ];

    /**
     * HTTP status code titles.
     */
    private const HTTP_TITLES = [
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        419 => 'Page Expired',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error',
        503 => 'Service Unavailable',
    ];

    /**
     * Constructor.
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ?TemplateInterface $template = null,
        private readonly ?ConfigInterface $config = null,
        private readonly ?EnvironmentManager $environment = null
    ) {}

    /**
     * Set container for accessing services.
     */
    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    /**
     * Initialize error handlers.
     */
    public function init(): void
    {
        if ($this->initialized) {
            return;
        }

        $this->initialized = true;

        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    /**
     * Handle PHP errors.
     */
    public function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        if (!(error_reporting() & $errno)) {
            return false;
        }

        $errorType = self::ERROR_TYPES[$errno] ?? 'Unknown Error';

        $errorInfo = $this->collectErrorInfo(
            message: $errstr,
            file: $errfile,
            line: $errline,
            type: $errorType,
            code: $errno,
            exception: null
        );

        $this->logError($errorInfo['message'], strtolower($errorType));
        $this->displayErrorPage($errorInfo);

        return true;
    }

    /**
     * Handle exceptions.
     */
    public function handleException(\Throwable $exception): void
    {
        $this->disableTemplate();

        $errorInfo = $this->collectErrorInfo(
            message: $exception->getMessage(),
            file: $exception->getFile(),
            line: $exception->getLine(),
            type: get_class($exception),
            code: 500,
            exception: $exception
        );

        $this->logError($errorInfo['message'], 'exception');
        $this->displayExceptionPage($errorInfo, $exception);
    }

    /**
     * Disable template rendering.
     */
    private function disableTemplate(): void
    {
        if ($this->template !== null) {
            $this->template->disable();
        }
    }

    /**
     * Handle shutdown errors.
     */
    public function handleShutdown(): void
    {
        $error = error_get_last();

        if ($error !== null && in_array($error['type'], self::FATAL_ERRORS, true)) {
            $errorType = self::ERROR_TYPES[$error['type']] ?? 'Fatal Error';

            $errorInfo = $this->collectErrorInfo(
                message: $error['message'],
                file: $error['file'],
                line: $error['line'],
                type: $errorType,
                code: 500,
                exception: null
            );

            $this->logError($errorInfo['message'], 'fatal');
            $this->displayErrorPage($errorInfo);
        }
    }

    /**
     * Collect full error information.
     */
    private function collectErrorInfo(
        string $message,
        string $file,
        int $line,
        string $type,
        int $code,
        ?\Throwable $exception
    ): array {
        $debug = $this->isDebugEnabled();

        // Collect basic info
        $info = [
            'type' => $type,
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'code' => $code,
            'status' => self::HTTP_TITLES[$code] ?? 'Error',
            'error_id' => bin2hex(random_bytes(8)),
            'debug' => $debug,
            'php_version' => PHP_VERSION,
            'framework_version' => self::FRAMEWORK_VERSION,
        ];

        // Collect stack trace
        if ($debug) {
            $info['trace'] = $this->formatTrace($exception);
            $info['trace_array'] = $exception?->getTrace() ?? debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
        }

        // Collect request info
        $info['request'] = $this->collectRequestInfo();

        // Collect route info
        $info['route'] = $this->collectRouteInfo();

        // Collect queries
        if ($debug) {
            $info['queries'] = $this->collectQueries();
        }

        return $info;
    }

    /**
     * Format stack trace.
     */
    private function formatTrace(?\Throwable $exception): string
    {
        if ($exception !== null) {
            return $exception->getTraceAsString();
        }

        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
        $trace = array_slice($trace, 2);

        $output = [];
        foreach ($trace as $i => $item) {
            $file = $item['file'] ?? 'unknown';
            $lineNum = $item['line'] ?? 0;
            $function = $item['function'] ?? 'unknown';
            $class = $item['class'] ?? '';
            $type = $item['type'] ?? '';

            $output[] = sprintf(
                '#%d %s%s%s() called at [%s:%d]',
                $i,
                $class,
                $type,
                $function,
                $file,
                $lineNum
            );
        }

        return implode("\n", $output);
    }

    /**
     * Collect request information.
     */
    private function collectRequestInfo(): array
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $url = ($_SERVER['HTTPS'] ?? 'off') === 'on' ? 'https' : 'http';
        $url .= '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $url .= $uri;

        // Headers
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headerName = str_replace('_', '-', substr($key, 5));
                $headers[$headerName] = $value;
            }
        }

        // Request body
        $body = null;
        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $rawInput = file_get_contents('php://input');
            if (!empty($rawInput)) {
                $body = $rawInput;
                // Try to parse as JSON
                $json = json_decode($rawInput, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $body = $json;
                }
            }
        }

        // GET/POST params
        $get = $_GET;
        $post = $_POST;

        return [
            'method' => $method,
            'uri' => $uri,
            'url' => $url,
            'headers' => $headers,
            'body' => $body,
            'get' => $get,
            'post' => $post,
        ];
    }

    /**
     * Collect route information.
     */
    private function collectRouteInfo(): array
    {
        if ($this->container === null) {
            return [];
        }

        try {
            $router = $this->container->get('router');

            return [
                'path' => $router->path ?? '/',
                'module' => method_exists($router, 'getModule') ? $router->getModule() : '',
                'controller' => method_exists($router, 'getController') ? $router->getController() : '',
                'action' => method_exists($router, 'getAction') ? $router->getAction() : '',
                'segments' => $router->segments ?? [],
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Collect database queries.
     */
    private function collectQueries(): array
    {
        if ($this->container === null) {
            return [];
        }

        try {
            $debug = $this->container->get('debug');
            if (method_exists($debug, 'getQueries')) {
                return $debug->getQueries();
            }
        } catch (\Exception $e) {
            // Debug service not available
        }

        return [];
    }

    /**
     * Display error page with full information.
     */
    private function displayErrorPage(array $errorInfo): void
    {
        http_response_code($errorInfo['code']);

        $view = new View\FullErrorView(data: $errorInfo);
        $view->render();
    }

    /**
     * Display exception page with full information.
     */
    private function displayExceptionPage(array $errorInfo, \Throwable $exception): void
    {
        http_response_code($errorInfo['code']);

        $view = new View\FullErrorView(data: $errorInfo, exception: $exception);
        $view->render();
    }

    /**
     * Log error using PSR-3.
     */
    private function logError(string $message, string $category): void
    {
        $level = $this->mapCategoryToLevel($category);
        $this->logger->log($level, $message, ['category' => $category]);
    }

    /**
     * Map error category to PSR-3 log level.
     */
    private function mapCategoryToLevel(string $category): string
    {
        return match ($category) {
            'fatal error', 'parse error', 'compile error', 'core error' => LogLevel::CRITICAL,
            'exception' => LogLevel::ERROR,
            'warning', 'core warning', 'compile warning', 'user warning' => LogLevel::WARNING,
            'notice', 'user notice', 'recoverable error' => LogLevel::NOTICE,
            'deprecated', 'user deprecated' => LogLevel::INFO,
            default => LogLevel::ERROR,
        };
    }

    /**
     * Display error page (legacy compatibility).
     */
    public function displayError(string $message, int $code = 500): void
    {
        $errorInfo = $this->collectErrorInfo(
            message: $message,
            file: 'unknown',
            line: 0,
            type: 'Error',
            code: $code,
            exception: null
        );

        $this->displayErrorPage($errorInfo);
    }

    /**
     * Display exception page (legacy compatibility).
     */
    public function displayException(\Throwable $exception): void
    {
        $errorInfo = $this->collectErrorInfo(
            message: $exception->getMessage(),
            file: $exception->getFile(),
            line: $exception->getLine(),
            type: get_class($exception),
            code: 500,
            exception: $exception
        );

        $this->displayExceptionPage($errorInfo, $exception);
    }

    /**
     * Display 404 page.
     */
    public function display404(string $message = 'Page not found'): void
    {
        http_response_code(404);

        $view = new View\NotFoundView(message: $message);
        $view->render();
    }

    /**
     * Check if debug mode is enabled.
     */
    private function isDebugEnabled(): bool
    {
        if ($this->config === null || $this->environment === null) {
            return false;
        }

        $debugConfig = $this->config->get('debug', []);
        $enabled = $debugConfig['enabled'] ?? false;

        return $enabled && !$this->environment->isProduction();
    }
}
