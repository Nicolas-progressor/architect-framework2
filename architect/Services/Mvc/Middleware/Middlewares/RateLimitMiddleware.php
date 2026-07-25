<?php

declare(strict_types=1);

namespace Architect\Services\Mvc\Middleware\Middlewares;

use Architect\Services\Mvc\Http\Response;
use Architect\Services\Mvc\Middleware\BaseMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Rate Limit Middleware.
 *
 * Limits the number of requests per time period.
 *
 * @package Architect\Services\Mvc\Middleware\Middlewares
 */
class RateLimitMiddleware extends BaseMiddleware
{
    /** @var int Maximum requests per window */
    protected int $maxRequests = 60;

    /** @var int Time window in seconds */
    protected int $windowSeconds = 60;

    /** @var string Key prefix for storage */
    protected string $keyPrefix = 'rate_limit:';

    /** @var callable|null Key generator function */
    protected $keyGenerator = null;

    /** @var array<string, array{count: int, reset_at: int}> Rate limit storage (in-memory) */
    protected static array $storage = [];

    /**
     * @inheritdoc
     */
    public function process(ServerRequestInterface $request, $handler): ResponseInterface
    {
        $key = $this->generateKey($request);
        $result = $this->checkRateLimit($key);

        // Add rate limit headers
        $response = $result['allowed']
            ? $handler->handle($request)
            : $this->tooManyRequests($result);

        return $this->addRateLimitHeaders($response, $result);
    }

    /**
     * Generate rate limit key.
     *
     * @param ServerRequestInterface $request Request instance
     * @return string
     */
    protected function generateKey(ServerRequestInterface $request): string
    {
        if ($this->keyGenerator !== null) {
            return $this->keyPrefix . ($this->keyGenerator)($request);
        }

        // Default: use IP address
        $ip = $request->getServerParams()['REMOTE_ADDR'] ?? '127.0.0.1';
        return $this->keyPrefix . $ip;
    }

    /**
     * Check rate limit.
     *
     * @param string $key Rate limit key
     * @return array{allowed: bool, remaining: int, reset_at: int, retry_after: int}
     */
    protected function checkRateLimit(string $key): array
    {
        $now = time();

        // Initialize or reset if window expired
        if (!isset(self::$storage[$key]) || self::$storage[$key]['reset_at'] <= $now) {
            self::$storage[$key] = [
                'count' => 0,
                'reset_at' => $now + $this->windowSeconds,
            ];
        }

        // Increment count
        self::$storage[$key]['count']++;

        $remaining = max(0, $this->maxRequests - self::$storage[$key]['count']);
        $allowed = self::$storage[$key]['count'] <= $this->maxRequests;
        $retryAfter = $allowed ? 0 : self::$storage[$key]['reset_at'] - $now;

        return [
            'allowed' => $allowed,
            'remaining' => $remaining,
            'reset_at' => self::$storage[$key]['reset_at'],
            'retry_after' => $retryAfter,
        ];
    }

    /**
     * Handle too many requests.
     *
     * @param array $result Rate limit result
     * @return ResponseInterface
     */
    protected function tooManyRequests(array $result): ResponseInterface
    {
        return Response::json([
            'error' => 'Too Many Requests',
            'message' => 'Rate limit exceeded. Please try again later.',
            'retry_after' => $result['retry_after'],
        ], 429);
    }

    /**
     * Add rate limit headers to response.
     *
     * @param ResponseInterface $response Response instance
     * @param array $result Rate limit result
     * @return ResponseInterface
     */
    protected function addRateLimitHeaders(ResponseInterface $response, array $result): ResponseInterface
    {
        $response = $response
            ->withHeader('X-RateLimit-Limit', (string) $this->maxRequests)
            ->withHeader('X-RateLimit-Remaining', (string) $result['remaining'])
            ->withHeader('X-RateLimit-Reset', (string) $result['reset_at']);

        if (!$result['allowed']) {
            $response = $response->withHeader('Retry-After', (string) $result['retry_after']);
        }

        return $response;
    }

    /**
     * Set maximum requests per window.
     *
     * @param int $max Maximum requests
     * @return self
     */
    public function setMaxRequests(int $max): self
    {
        $this->maxRequests = $max;
        return $this;
    }

    /**
     * Set time window in seconds.
     *
     * @param int $seconds Window duration
     * @return self
     */
    public function setWindowSeconds(int $seconds): self
    {
        $this->windowSeconds = $seconds;
        return $this;
    }

    /**
     * Set key prefix.
     *
     * @param string $prefix Key prefix
     * @return self
     */
    public function setKeyPrefix(string $prefix): self
    {
        $this->keyPrefix = $prefix;
        return $this;
    }

    /**
     * Set custom key generator.
     *
     * @param callable $generator Key generator function
     * @return self
     */
    public function setKeyGenerator(callable $generator): self
    {
        $this->keyGenerator = $generator;
        return $this;
    }

    /**
     * Clear storage (useful for testing).
     *
     * @return void
     */
    public static function clearStorage(): void
    {
        self::$storage = [];
    }
}
