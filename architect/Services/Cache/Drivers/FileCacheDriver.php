<?php

declare(strict_types=1);

namespace Architect\Services\Cache\Drivers;

use InvalidArgumentException;
use Psr\SimpleCache\InvalidArgumentException as PsrInvalidArgumentException;

/**
 * File‑based cache driver.
 * Stores each cache entry as a serialized file.
 */
class FileCacheDriver extends AbstractCacheDriver
{
    /**
     * Directory where cache files are stored.
     */
    private string $directory;

    /**
     * File permissions for created directories and files.
     */
    private int $directoryPermissions = 0755;
    private int $filePermissions = 0644;

    /**
     * @param string $directory Cache directory path
     * @param int    $directoryPermissions Permissions for directories (default 0755)
     * @param int    $filePermissions Permissions for files (default 0644)
     *
     * @throws InvalidArgumentException If directory cannot be created or is not writable.
     */
    public function __construct(
        string $directory,
        int $directoryPermissions = 0755,
        int $filePermissions = 0644
    ) {
        $this->directory = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->directoryPermissions = $directoryPermissions;
        $this->filePermissions = $filePermissions;

        if (!is_dir($this->directory) && !@mkdir($this->directory, $this->directoryPermissions, true)) {
            throw new InvalidArgumentException(
                sprintf('Cache directory "%s" could not be created.', $this->directory)
            );
        }

        if (!is_writable($this->directory)) {
            throw new InvalidArgumentException(
                sprintf('Cache directory "%s" is not writable.', $this->directory)
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $key = $this->prefixKey($this->normalizeKey($key));
        $path = $this->path($key);

        if (!file_exists($path)) {
            return $default;
        }

        $data = $this->readFile($path);
        if ($data === null) {
            // File corrupted or expired, delete it
            @unlink($path);
            return $default;
        }

        [$expire, $value] = $data;
        if ($expire !== null && $expire < time()) {
            @unlink($path);
            return $default;
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $key = $this->prefixKey($this->normalizeKey($key));
        $path = $this->path($key);
        $expiration = $this->normalizeTtl($ttl);

        if ($expiration !== null && $expiration <= 0) {
            // Immediate expiration = delete
            $this->delete($key);
            return true;
        }

        $expireAt = $expiration === null ? null : (time() + $expiration);
        $data = serialize([$expireAt, $value]);

        $tempPath = $path . '.' . uniqid('', true) . '.tmp';
        if (file_put_contents($tempPath, $data, LOCK_EX) === false) {
            return false;
        }

        if (!chmod($tempPath, $this->filePermissions)) {
            @unlink($tempPath);
            return false;
        }

        if (!rename($tempPath, $path)) {
            @unlink($tempPath);
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        $key = $this->prefixKey($this->normalizeKey($key));
        $path = $this->path($key);

        if (file_exists($path)) {
            return @unlink($path);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        $success = true;
        $pattern = $this->directory . '*';
        $files = glob($pattern);
        foreach ($files as $file) {
            if (is_file($file) && !@unlink($file)) {
                $success = false;
            }
        }
        return $success;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        $key = $this->prefixKey($this->normalizeKey($key));
        $path = $this->path($key);

        if (!file_exists($path)) {
            return false;
        }

        $data = $this->readFile($path);
        if ($data === null) {
            @unlink($path);
            return false;
        }

        [$expire] = $data;
        if ($expire !== null && $expire < time()) {
            @unlink($path);
            return false;
        }

        return true;
    }

    /**
     * Get the full filesystem path for a cache key.
     */
    private function path(string $key): string
    {
        // Sanitize key to avoid directory traversal
        $hash = sha1($key);
        $subDir = substr($hash, 0, 2);
        $dir = $this->directory . $subDir . DIRECTORY_SEPARATOR;

        if (!is_dir($dir) && !@mkdir($dir, $this->directoryPermissions, true)) {
            throw new InvalidArgumentException(
                sprintf('Unable to create cache subdirectory "%s".', $dir)
            );
        }

        return $dir . $hash . '.cache';
    }

    /**
     * Read and unserialize a cache file.
     *
     * @return array{?int, mixed}|null Returns [expiration, value] or null on failure.
     */
    private function readFile(string $path): ?array
    {
        if (!is_readable($path)) {
            return null;
        }

        $content = @file_get_contents($path);
        if ($content === false) {
            return null;
        }

        $data = @unserialize($content, ['allowed_classes' => true]);
        if ($data === false || !is_array($data) || count($data) !== 2) {
            return null;
        }

        return $data;
    }

    /**
     * Get the cache directory.
     */
    public function getDirectory(): string
    {
        return $this->directory;
    }
}