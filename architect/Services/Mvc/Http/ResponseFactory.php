<?php

declare(strict_types=1);

namespace Architect\Services\Mvc\Http;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

/**
 * PSR-17 Response Factory implementation.
 * 
 * Factory for creating HTTP responses and streams.
 * 
 * @package Architect\Services\Mvc\Http
 */
class ResponseFactory implements ResponseFactoryInterface, StreamFactoryInterface
{
    /**
     * @inheritdoc
     */
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return new Response($code, [], null, '1.1');
    }

    /**
     * @inheritdoc
     */
    public function createStream(string $content = ''): StreamInterface
    {
        return new Stream($content);
    }

    /**
     * @inheritdoc
     */
    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        return Stream::fromFile($filename, $mode);
    }

    /**
     * @inheritdoc
     */
    public function createStreamFromResource($resource): StreamInterface
    {
        if (!is_resource($resource)) {
            throw new \InvalidArgumentException('Parameter must be a valid resource');
        }

        $stream = new Stream('');
        $stream->close();

        // Use reflection to set the resource
        $reflection = new \ReflectionClass($stream);
        $property = $reflection->getProperty('resource');
        $property->setAccessible(true);
        $property->setValue($stream, $resource);

        return $stream;
    }

    // === Convenience Factory Methods ===

    /**
     * Create JSON response.
     * 
     * @param mixed $data Data to encode
     * @param int $status HTTP status code
     * @param array<string, string|string[]> $headers Additional headers
     * @return ResponseInterface
     */
    public function createJsonResponse(
        mixed $data,
        int $status = 200,
        array $headers = []
    ): ResponseInterface {
        $json = json_encode($data, JSON_THROW_ON_ERROR);

        $headers['Content-Type'] = 'application/json';

        return new Response($status, $headers, $this->createStream($json));
    }

    /**
     * Create HTML response.
     * 
     * @param string $html HTML content
     * @param int $status HTTP status code
     * @param array<string, string|string[]> $headers Additional headers
     * @return ResponseInterface
     */
    public function createHtmlResponse(
        string $html,
        int $status = 200,
        array $headers = []
    ): ResponseInterface {
        $headers['Content-Type'] = 'text/html; charset=utf-8';

        return new Response($status, $headers, $this->createStream($html));
    }

    /**
     * Create redirect response.
     * 
     * @param string $url Redirect URL
     * @param int $status HTTP status code
     * @return ResponseInterface
     */
    public function createRedirectResponse(string $url, int $status = 302): ResponseInterface
    {
        return $this->createResponse($status)
            ->withHeader('Location', $url);
    }

    /**
     * Create error response.
     * 
     * @param int $status HTTP status code
     * @param string $message Error message
     * @return ResponseInterface
     */
    public function createErrorResponse(int $status, string $message = ''): ResponseInterface
    {
        $response = $this->createResponse($status);

        if ($message !== '') {
            $response = $response
                ->withHeader('Content-Type', 'text/plain; charset=utf-8')
                ->withBody($this->createStream($message));
        }

        return $response;
    }

    /**
     * Create 404 Not Found response.
     * 
     * @param string $message Error message
     * @return ResponseInterface
     */
    public function createNotFoundResponse(string $message = 'Not Found'): ResponseInterface
    {
        return $this->createErrorResponse(404, $message);
    }

    /**
     * Create 403 Forbidden response.
     * 
     * @param string $message Error message
     * @return ResponseInterface
     */
    public function createForbiddenResponse(string $message = 'Forbidden'): ResponseInterface
    {
        return $this->createErrorResponse(403, $message);
    }

    /**
     * Create 500 Internal Server Error response.
     * 
     * @param string $message Error message
     * @return ResponseInterface
     */
    public function createServerErrorResponse(string $message = 'Internal Server Error'): ResponseInterface
    {
        return $this->createErrorResponse(500, $message);
    }
}
