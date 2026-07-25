<?php

declare(strict_types=1);

namespace Architect\Auth\Services;

use Architect\Auth\Contracts\TokenStorageInterface;

class SessionStorage implements TokenStorageInterface
{
    private const PREFIX = 'auth_token_';

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function store(string $key, string $token, ?int $ttl = null): bool
    {
        $_SESSION[$this->prefixKey($key)] = [
            'token' => $token,
            'expires' => $ttl ? time() + $ttl : null,
        ];
        return true;
    }

    public function retrieve(string $key): ?string
    {
        $data = $_SESSION[$this->prefixKey($key)] ?? null;
        if (!$data) {
            return null;
        }

        // Проверить срок действия
        if (isset($data['expires']) && $data['expires'] < time()) {
            $this->forget($key);
            return null;
        }

        return $data['token'];
    }

    public function forget(string $key): bool
    {
        unset($_SESSION[$this->prefixKey($key)]);
        return true;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$this->prefixKey($key)]);
    }

    public function clear(): void
    {
        foreach ($_SESSION as $key => $value) {
            if (str_starts_with($key, self::PREFIX)) {
                unset($_SESSION[$key]);
            }
        }
    }

    private function prefixKey(string $key): string
    {
        return self::PREFIX . $key;
    }
}