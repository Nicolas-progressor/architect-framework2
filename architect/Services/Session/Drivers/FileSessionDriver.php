<?php

declare(strict_types=1);

namespace Architect\Services\Session\Drivers;

use Architect\Services\Session\Contracts\SessionDriverInterface;

/**
 * File-based session driver.
 */
class FileSessionDriver implements SessionDriverInterface
{
    private string $sessionId = '';
    private string $sessionName = 'architect_session';
    private int $lifetime = 1440;
    private bool $started = false;
    private array $data = [];

    public function __construct(
        private readonly string $storagePath = '',
        ?int $lifetime = null,
    ) {
        if ($lifetime !== null) {
            $this->lifetime = $lifetime;
        }

        if ($this->storagePath === '') {
            $this->storagePath = sys_get_temp_dir();
        }
    }

    public function start(): bool
    {
        if ($this->started) {
            return true;
        }

        if ($this->sessionId === '') {
            $this->sessionId = $this->generateId();
        }

        $file = $this->getPath();

        if (file_exists($file)) {
            $content = file_get_contents($file);
            if ($content !== false) {
                $decoded = json_decode($content, true);
                if (is_array($decoded)) {
                    // Check expiry
                    if (isset($decoded['__expires']) && $decoded['__expires'] < time()) {
                        $this->data = [];
                        $this->deleteFile();
                    } else {
                        $this->data = $decoded;
                        unset($this->data['__expires']);
                    }
                }
            }
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
        if ($deleteOld) {
            $this->deleteFile();
        }

        $this->sessionId = $this->generateId();
        return true;
    }

    public function destroy(): bool
    {
        $result = $this->deleteFile();
        $this->data = [];
        $this->started = false;
        return $result;
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
        $toSave['__expires'] = time() + $this->lifetime;

        $json = json_encode($toSave, JSON_THROW_ON_ERROR);

        $dir = dirname($this->getPath());
        if (!is_dir($dir)) {
            mkdir($dir, 0o755, true);
        }

        $result = file_put_contents($this->getPath(), $json);
        return $result !== false;
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

    private function getPath(): string
    {
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '', $this->sessionName);
        return $this->storagePath . '/' . $name . '_' . $this->sessionId . '.json';
    }

    private function generateId(): string
    {
        return bin2hex(random_bytes(16));
    }

    private function deleteFile(): bool
    {
        $file = $this->getPath();
        if (file_exists($file)) {
            return unlink($file);
        }
        return true;
    }
}
