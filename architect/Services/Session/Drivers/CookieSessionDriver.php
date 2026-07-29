<?php

declare(strict_types=1);

namespace Architect\Services\Session\Drivers;

use Architect\Services\Session\Contracts\SessionDriverInterface;

/**
 * Cookie-based session driver.
 *
 * Stores session data in an encrypted cookie. No server-side storage needed.
 */
class CookieSessionDriver implements SessionDriverInterface
{
    private string $sessionId = '';
    private string $sessionName = 'architect_cookie';
    private int $lifetime = 1440;
    private bool $started = false;
    private array $data = [];
    private string $secret = '';

    public function __construct(
        string $secret = '',
        ?int $lifetime = null,
    ) {
        $this->secret = $secret ?: ($_SERVER['APP_SECRET'] ?? 'architect-default-secret');
        if ($lifetime !== null) {
            $this->lifetime = $lifetime;
        }
    }

    public function start(): bool
    {
        if ($this->started) {
            return true;
        }

        $raw = $_COOKIE[$this->sessionName] ?? '';

        if ($raw !== '') {
            $decoded = $this->decrypt($raw);
            if (is_array($decoded)) {
                if (isset($decoded['__expires']) && $decoded['__expires'] < time()) {
                    $this->data = [];
                } else {
                    $this->data = $decoded;
                    unset($this->data['__expires']);
                    $this->sessionId = $decoded['__id'] ?? $this->generateId();
                    unset($this->data['__id']);
                }
            }
        }

        if ($this->sessionId === '') {
            $this->sessionId = $this->generateId();
        }

        $this->started = true;
        return true;
    }

    public function isActive(): bool
    {
        return $this->started;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function remove(string $key): void
    {
        unset($this->data[$key]);
    }

    public function all(): array
    {
        return $this->data;
    }

    public function put(array $data): void
    {
        $this->data = array_merge($this->data, $data);
    }

    public function forget(array $keys): void
    {
        foreach ($keys as $key) {
            unset($this->data[$key]);
        }
    }

    public function clear(): void
    {
        $this->data = [];
    }

    public function getId(): string
    {
        return $this->sessionId;
    }

    public function setId(string $id): void
    {
        $this->sessionId = $id;
    }

    public function regenerate(bool $deleteOld = true): bool
    {
        $this->data = $deleteOld ? [] : $this->data;
        $this->sessionId = $this->generateId();
        return true;
    }

    public function destroy(): bool
    {
        $this->data = [];
        $this->started = false;
        $this->sessionId = '';

        if (headers_sent()) {
            return false;
        }

        setcookie($this->sessionName, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        return true;
    }

    public function getName(): string
    {
        return $this->sessionName;
    }

    public function setName(string $name): void
    {
        $this->sessionName = $name;
    }

    public function getLifetime(): int
    {
        return $this->lifetime;
    }

    public function setLifetime(int $seconds): void
    {
        $this->lifetime = $seconds;
    }

    public function save(): bool
    {
        if (!$this->started) {
            return false;
        }

        $toSave = $this->data;
        $toSave['__id'] = $this->sessionId;
        $toSave['__expires'] = time() + $this->lifetime;

        $encrypted = $this->encrypt($toSave);

        if (headers_sent()) {
            return false;
        }

        setcookie($this->sessionName, $encrypted, [
            'expires' => time() + $this->lifetime,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        $_COOKIE[$this->sessionName] = $encrypted;

        return true;
    }

    public function meta(): array
    {
        return [
            'id' => $this->sessionId,
            'name' => $this->sessionName,
            'lifetime' => $this->lifetime,
            'active' => $this->started,
        ];
    }

    /**
     * Get raw encrypted cookie value (for testing/debug).
     */
    public function getRawCookie(): string
    {
        return $_COOKIE[$this->sessionName] ?? '';
    }

    private function encrypt(array $data): string
    {
        $json = json_encode($data, JSON_THROW_ON_ERROR);
        $iv = random_bytes(16);
        $key = hash('sha256', $this->secret, true);

        $encrypted = openssl_encrypt($json, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        if ($encrypted === false) {
            throw new \RuntimeException('Failed to encrypt session data');
        }

        return base64_encode($iv . $encrypted);
    }

    private function decrypt(string $encoded): ?array
    {
        $raw = base64_decode($encoded, true);
        if ($raw === false || strlen($raw) < 17) {
            return null;
        }

        $iv = substr($raw, 0, 16);
        $encrypted = substr($raw, 16);
        $key = hash('sha256', $this->secret, true);

        $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        if ($decrypted === false) {
            return null;
        }

        $data = json_decode($decrypted, true);
        return is_array($data) ? $data : null;
    }

    private function generateId(): string
    {
        return bin2hex(random_bytes(16));
    }
}
