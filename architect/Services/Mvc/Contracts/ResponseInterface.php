<?php

declare(strict_types=1);

namespace Architect\Services\Mvc\Contracts;

/**
 * Interface for HTTP response handling.
 * 
 * Defines the contract for response objects including
 * status codes, headers, redirects, and content.
 * 
 * @package Architect\Services\Mvc\Contracts
 */
interface ResponseInterface
{
    /**
     * Set HTTP status code.
     * 
     * @param int $code HTTP status code (e.g., 200, 404, 500)
     * @return self
     */
    public function setStatusCode(int $code): self;

    /**
     * Set response header.
     * 
     * @param string $name Header name
     * @param string $value Header value
     * @return self
     */
    public function setHeader(string $name, string $value): self;

    /**
     * Set response content.
     * 
     * @param string $content Response body
     * @return self
     */
    public function setContent(string $content): self;

    /**
     * Set JSON content.
     * 
     * Encodes data to JSON and sets Content-Type header.
     * 
     * @param mixed $data Data to encode
     * @param int $options JSON encode options
     * @return self
     */
    public function withJson(mixed $data, int $options = 0): self;

    /**
     * Send JSON response and exit.
     * 
     * Convenience method for API responses.
     * Sets Content-Type, outputs JSON, and terminates execution.
     * 
     * @param mixed $data Data to encode
     * @param int $statusCode HTTP status code
     * @param int $options JSON encode options
     */
    public function sendJson(mixed $data, int $statusCode = 200, int $options = 0): never;

    /**
     * Get response type.
     * 
     * @return string Response type (html, json, redirect, text)
     */
    public function getType(): string;

    /**
     * Set response type.
     * 
     * @param string $type Response type
     * @return self
     */
    public function withType(string $type): self;

    /**
     * Prepare redirect response.
     * 
     * @param string $url Redirect URL
     * @param int $status HTTP status code
     * @return self
     */
    public function withRedirect(string $url, int $status = 302): self;

    /**
     * Redirect to URL (immediate).
     * 
     * Sets Location header.
     * 
     * @param string $url Redirect URL
     * @return self
     */
    public function redirect(string $url): self;

    /**
     * Abort with error code.
     * 
     * Sets status code and content.
     * 
     * @param int $code HTTP status code
     * @param string $message Error message
     * @return self
     */
    public function abort(int $code, string $message = ''): self;

    /**
     * Send response to client.
     * 
     * Outputs headers and content.
     */
    public function send(): void;

    /**
     * Get response content.
     * 
     * @return string
     */
    public function getContent(): string;

    /**
     * Check if response is redirect.
     * 
     * @return bool
     */
    public function isRedirect(): bool;

    /**
     * Check if response is JSON.
     * 
     * @return bool
     */
    public function isJson(): bool;
}
