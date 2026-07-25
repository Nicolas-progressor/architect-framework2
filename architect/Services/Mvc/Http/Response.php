<?php

declare(strict_types=1);

namespace Architect\Services\Mvc\Http;

use Architect\Services\Mvc\Contracts\ResponseInterface as MvcResponseInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * PSR-7 Response implementation.
 *
 * Provides a standard HTTP response that follows PSR-7 specification.
 * Also implements MvcResponseInterface for framework compatibility.
 *
 * @package Architect\Services\Mvc\Http
 */
class Response implements PsrResponseInterface, MvcResponseInterface
{
    /** @var string HTTP protocol version */
    private string $protocolVersion = '1.1';

    /** @var array<string, array<string>> Response headers */
    private array $headers = [];

    /** @var array<string, string> Header names normalized to lowercase */
    private array $headerNames = [];

    /** @var StreamInterface Response body stream */
    private StreamInterface $body;

    /** @var int HTTP status code */
    private int $statusCode = 200;

    /** @var string Reason phrase */
    private string $reasonPhrase = '';

    /** @var string|null Response type (html, json, redirect, text) */
    private ?string $type = 'html';

    /** @var mixed JSON data for json responses */
    private mixed $jsonData = null;

    /** @var int JSON encode options */
    private int $jsonOptions = 0;

    /** @var array<int, string> Standard reason phrases */
    private const REASON_PHRASES = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-status',
        208 => 'Already Reported',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        511 => 'Network Authentication Required',
    ];

    /**
     * Create response instance.
     *
     * @param int $status HTTP status code
     * @param array<string, string|string[]> $headers Response headers
     * @param StreamInterface|null $body Response body
     * @param string $protocolVersion HTTP protocol version
     */
    public function __construct(
        int $status = 200,
        array $headers = [],
        ?StreamInterface $body = null,
        string $protocolVersion = '1.1'
    ) {
        $this->statusCode = $this->validateStatus($status);
        $this->protocolVersion = $protocolVersion;
        $this->body = $body ?? new Stream('');

        $this->setHeaders($headers);
    }

    // === PSR-7 Methods ===

    /**
     * @inheritdoc
     */
    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    /**
     * @inheritdoc
     */
    public function withProtocolVersion(string $version): self
    {
        if ($this->protocolVersion === $version) {
            return $this;
        }

        $new = clone $this;
        $new->protocolVersion = $version;

        return $new;
    }

    /**
     * @inheritdoc
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @inheritdoc
     */
    public function hasHeader(string $name): bool
    {
        return isset($this->headerNames[strtolower($name)]);
    }

    /**
     * @inheritdoc
     */
    public function getHeader(string $name): array
    {
        $name = strtolower($name);

        if (!isset($this->headerNames[$name])) {
            return [];
        }

        $headerName = $this->headerNames[$name];

        return $this->headers[$headerName];
    }

    /**
     * @inheritdoc
     */
    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    /**
     * @inheritdoc
     */
    public function withHeader(string $name, $value): self
    {
        $value = $this->validateHeader($value);
        $normalized = strtolower($name);

        $new = clone $this;

        if (isset($new->headerNames[$normalized])) {
            unset($new->headers[$new->headerNames[$normalized]]);
        }

        $new->headerNames[$normalized] = $name;
        $new->headers[$name] = $value;

        return $new;
    }

    /**
     * @inheritdoc
     */
    public function withAddedHeader(string $name, $value): self
    {
        $value = $this->validateHeader($value);
        $normalized = strtolower($name);

        $new = clone $this;

        if (isset($new->headerNames[$normalized])) {
            $headerName = $new->headerNames[$normalized];
            $new->headers[$headerName] = array_merge($new->headers[$headerName], $value);
        } else {
            $new->headerNames[$normalized] = $name;
            $new->headers[$name] = $value;
        }

        return $new;
    }

    /**
     * @inheritdoc
     */
    public function withoutHeader(string $name): self
    {
        $normalized = strtolower($name);

        if (!isset($this->headerNames[$normalized])) {
            return $this;
        }

        $headerName = $this->headerNames[$normalized];

        $new = clone $this;
        unset($new->headers[$headerName], $new->headerNames[$normalized]);

        return $new;
    }

    /**
     * @inheritdoc
     */
    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    /**
     * @inheritdoc
     */
    public function withBody(StreamInterface $body): self
    {
        if ($body === $this->body) {
            return $this;
        }

        $new = clone $this;
        $new->body = $body;

        return $new;
    }

    /**
     * @inheritdoc
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @inheritdoc
     */
    public function withStatus(int $code, string $reasonPhrase = ''): self
    {
        $code = $this->validateStatus($code);

        if ($this->statusCode === $code && $this->reasonPhrase === $reasonPhrase) {
            return $this;
        }

        $new = clone $this;
        $new->statusCode = $code;

        if ($reasonPhrase === '' && isset(self::REASON_PHRASES[$code])) {
            $reasonPhrase = self::REASON_PHRASES[$code];
        }

        $new->reasonPhrase = $reasonPhrase;

        return $new;
    }

    /**
     * @inheritdoc
     */
    public function getReasonPhrase(): string
    {
        if ($this->reasonPhrase !== '') {
            return $this->reasonPhrase;
        }

        return self::REASON_PHRASES[$this->statusCode] ?? '';
    }

    // === Response Type ===

    /**
     * Get response type.
     *
     * @return string Response type (html, json, redirect, text)
     */
    public function getType(): string
    {
        return $this->type ?? 'html';
    }

    /**
     * Set response type.
     *
     * @param string $type Response type
     * @return self
     */
    public function withType(string $type): self
    {
        $new = clone $this;
        $new->type = $type;

        return $new;
    }

    // === Convenience Methods ===

    /**
     * Get response content as string.
     *
     * @return string
     */
    public function getContent(): string
    {
        return (string) $this->body;
    }

    /**
     * Create response with content.
     *
     * @param string $content Response content
     * @return self
     */
    public function withContent(string $content): self
    {
        $new = clone $this;
        $new->body = new Stream($content);
        $new->type = 'html';

        return $new;
    }

    /**
     * Set JSON data for response.
     *
     * @param mixed $data Data to encode
     * @param int $options JSON encode options
     * @return self
     */
    public function withJson(mixed $data, int $options = 0): self
    {
        $new = clone $this;
        $new->type = 'json';
        $new->jsonData = $data;
        $new->jsonOptions = $options;
        $new->body = new Stream(json_encode($data, $options | JSON_THROW_ON_ERROR));
        $new->headerNames['content-type'] = 'Content-Type';
        $new->headers['Content-Type'] = ['application/json'];

        return $new;
    }

    /**
     * Get JSON data.
     *
     * @return mixed
     */
    public function getJsonData(): mixed
    {
        return $this->jsonData;
    }

    /**
     * Send JSON response and exit.
     *
     * @param mixed $data Data to encode
     * @param int $statusCode HTTP status code
     * @param int $options JSON encode options
     */
    public function sendJson(mixed $data, int $statusCode = 200, int $options = 0): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, $options | JSON_THROW_ON_ERROR);
        exit;
    }

    /**
     * Create HTML response.
     *
     * @param string $html HTML content
     * @return self
     */
    public static function html(string $html): self
    {
        return (new self())
            ->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->withContent($html);
    }

    /**
     * Create redirect response (factory method).
     *
     * @param string $url Redirect URL
     * @param int $status HTTP status code (302 by default)
     * @return self
     */
    public static function location(string $url, int $status = 302): self
    {
        return (new self($status))
            ->withHeader('Location', $url)
            ->withType('redirect');
    }

    /**
     * Create text response.
     *
     * @param string $text Text content
     * @return self
     */
    public static function text(string $text): self
    {
        return (new self())
            ->withHeader('Content-Type', 'text/plain; charset=utf-8')
            ->withContent($text);
    }

    /**
     * Check if response is redirect.
     *
     * @return bool
     */
    public function isRedirect(): bool
    {
        return $this->type === 'redirect' || ($this->statusCode >= 300 && $this->statusCode < 400);
    }

    /**
     * Check if response is JSON.
     *
     * @return bool
     */
    public function isJson(): bool
    {
        return $this->type === 'json';
    }

    /**
     * Check if response is successful.
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * Check if response is client error.
     *
     * @return bool
     */
    public function isClientError(): bool
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    /**
     * Check if response is server error.
     *
     * @return bool
     */
    public function isServerError(): bool
    {
        return $this->statusCode >= 500 && $this->statusCode < 600;
    }

    /**
     * Check if response is informational.
     *
     * @return bool
     */
    public function isInformational(): bool
    {
        return $this->statusCode >= 100 && $this->statusCode < 200;
    }

    // === MvcResponseInterface Methods ===

    /**
     * Set HTTP status code.
     *
     * @param int $code HTTP status code
     * @return self
     */
    public function setStatusCode(int $code): self
    {
        return $this->withStatus($code);
    }

    /**
     * Set response header.
     *
     * @param string $name Header name
     * @param string $value Header value
     * @return self
     */
    public function setHeader(string $name, string $value): self
    {
        return $this->withHeader($name, $value);
    }

    /**
     * Set response content.
     *
     * @param string $content Response body
     * @return self
     */
    public function setContent(string $content): self
    {
        return $this->withContent($content);
    }

    /**
     * Redirect to URL (immediate).
     *
     * @param string $url Redirect URL
     * @return self
     */
    public function redirect(string $url): self
    {
        // Send redirect immediately for MVC compatibility
        header('Location: ' . $url, true, 302);
        exit;
    }

    /**
     * Prepare redirect (without immediate exit).
     *
     * @param string $url Redirect URL
     * @param int $status HTTP status code
     * @return self
     */
    public function withRedirect(string $url, int $status = 302): self
    {
        return $this->withStatus($status)
            ->withHeader('Location', $url)
            ->withType('redirect');
    }

    /**
     * Abort with error code.
     *
     * @param int $code HTTP status code
     * @param string $message Error message
     * @return self
     */
    public function abort(int $code, string $message = ''): self
    {
        return $this->withStatus($code)->withContent($message);
    }

    /**
     * Send response to client.
     *
     * Outputs headers and content.
     */
    public function send(): void
    {
        // Send status code
        $protocol = $this->getProtocolVersion();
        $status = $this->getStatusCode();
        $reason = $this->getReasonPhrase();

        header("HTTP/{$protocol} {$status} {$reason}", true, $status);

        // Send headers
        foreach ($this->headers as $name => $values) {
            foreach ($values as $value) {
                header("{$name}: {$value}", true);
            }
        }

        // Send body
        $body = $this->getBody();
        if ($body->isReadable()) {
            $body->rewind();
            echo $body->getContents();
        }
    }

    // === Helper Methods ===

    /**
     * Set headers from array.
     *
     * @param array<string, string|string[]> $headers Headers to set
     */
    private function setHeaders(array $headers): void
    {
        foreach ($headers as $name => $value) {
            $value = $this->validateHeader($value);
            $normalized = strtolower($name);

            $this->headerNames[$normalized] = $name;
            $this->headers[$name] = $value;
        }
    }

    /**
     * Validate header value.
     *
     * @param mixed $value Header value
     * @return array<string>
     */
    private function validateHeader(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        return [$value];
    }

    /**
     * Validate HTTP status code.
     *
     * @param int $status Status code
     * @return int
     */
    private function validateStatus(int $status): int
    {
        if ($status < 100 || $status > 599) {
            throw new \InvalidArgumentException(
                "Invalid status code: {$status}. Must be between 100 and 599."
            );
        }

        return $status;
    }
}
