<?php

declare(strict_types=1);

namespace Architect\Services\Mvc\Http;

use Psr\Http\Message\ResponseInterface;

/**
 * Response Emitter.
 *
 * Sends HTTP response to the client.
 *
 * @package Architect\Services\Mvc\Http
 */
class ResponseEmitter
{
    /** @var bool Whether to use chunked transfer encoding */
    private bool $useChunkedEncoding = false;

    /** @var int Maximum buffer size for chunked encoding */
    private int $bufferSize = 8192;

    /**
     * Emit response to the client.
     *
     * @param ResponseInterface $response Response to emit
     */
    public function emit(ResponseInterface $response): void
    {
        $this->emitHeaders($response);
        $this->emitStatusLine($response);
        $this->emitBody($response);
    }

    /**
     * Emit only headers.
     *
     * @param ResponseInterface $response Response
     */
    public function emitHeaders(ResponseInterface $response): void
    {
        if (headers_sent()) {
            return;
        }

        // Set HTTP status code
        $statusCode = $response->getStatusCode();
        $reasonPhrase = $response->getReasonPhrase();

        header(sprintf(
            'HTTP/%s %d %s',
            $response->getProtocolVersion(),
            $statusCode,
            $reasonPhrase
        ), true, $statusCode);

        // Set headers
        foreach ($response->getHeaders() as $name => $values) {
            $this->emitHeader($name, $values);
        }
    }

    /**
     * Emit status line.
     *
     * @param ResponseInterface $response Response
     */
    public function emitStatusLine(ResponseInterface $response): void
    {
        if (headers_sent()) {
            return;
        }

        http_response_code($response->getStatusCode());
    }

    /**
     * Emit response body.
     *
     * @param ResponseInterface $response Response
     */
    public function emitBody(ResponseInterface $response): void
    {
        $body = $response->getBody();

        if ($body->isSeekable()) {
            $body->rewind();
        }

        if (!$body->isReadable()) {
            return;
        }

        // Check if we should use chunked encoding
        if ($this->useChunkedEncoding && !$response->hasHeader('Content-Length')) {
            $this->emitChunkedBody($body);
        } else {
            $this->emitStreamedBody($body);
        }
    }

    /**
     * Emit body with chunked encoding.
     *
     * @param \Psr\Http\Message\StreamInterface $body Body stream
     */
    private function emitChunkedBody($body): void
    {
        while (!$body->eof()) {
            $chunk = $body->read($this->bufferSize);

            if ($chunk !== '') {
                printf("%x\r\n%s\r\n", strlen($chunk), $chunk);
            }
        }

        echo "0\r\n\r\n";
    }

    /**
     * Emit body as stream.
     *
     * @param \Psr\Http\Message\StreamInterface $body Body stream
     */
    private function emitStreamedBody($body): void
    {
        while (!$body->eof()) {
            echo $body->read($this->bufferSize);
        }
    }

    /**
     * Emit a single header.
     *
     * @param string $name Header name
     * @param array<string> $values Header values
     */
    private function emitHeader(string $name, array $values): void
    {
        $first = true;

        foreach ($values as $value) {
            header(
                sprintf('%s: %s', $name, $value),
                $first,
                null
            );
            $first = false;
        }
    }

    /**
     * Set whether to use chunked encoding.
     *
     * @param bool $useChunked Use chunked encoding
     * @return self
     */
    public function withChunkedEncoding(bool $useChunked = true): self
    {
        $new = clone $this;
        $new->useChunkedEncoding = $useChunked;

        return $new;
    }

    /**
     * Set buffer size for streaming.
     *
     * @param int $bufferSize Buffer size in bytes
     * @return self
     */
    public function withBufferSize(int $bufferSize): self
    {
        $new = clone $this;
        $new->bufferSize = $bufferSize;

        return $new;
    }

    /**
     * Create emitter with default settings.
     *
     * @return self
     */
    public static function create(): self
    {
        return new self();
    }
}
