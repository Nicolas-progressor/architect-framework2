<?php

declare(strict_types=1);

namespace Architect\Services\Performance\Storage;

use Architect\Services\Performance\Contracts\MetricStorageInterface;

class SessionStorage implements MetricStorageInterface
{
    private string $sessionKey;
    private int $maxEntries;

    public function __construct(string $sessionKey = 'performance_metrics', int $maxEntries = 100)
    {
        $this->sessionKey = $sessionKey;
        $this->maxEntries = $maxEntries;

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION[$this->sessionKey])) {
            $_SESSION[$this->sessionKey] = [];
        }
    }

    public function store(array $metrics): bool
    {
        try {
            $metrics['timestamp'] = microtime(true);
            $metrics['id'] = uniqid('metric_', true);

            $_SESSION[$this->sessionKey][] = $metrics;

            // Ограничение количества записей
            if (count($_SESSION[$this->sessionKey]) > $this->maxEntries) {
                array_shift($_SESSION[$this->sessionKey]);
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function retrieve(string $id): ?array
    {
        foreach ($_SESSION[$this->sessionKey] as $metric) {
            if (isset($metric['id']) && $metric['id'] === $id) {
                return $metric;
            }
        }

        return null;
    }

    public function list(int $limit = 100, int $offset = 0): array
    {
        $metrics = $_SESSION[$this->sessionKey] ?? [];

        // Сортировка по времени (новые первыми)
        usort($metrics, function ($a, $b) {
            return ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0);
        });

        return array_slice($metrics, $offset, $limit);
    }

    public function delete(string $id): bool
    {
        foreach ($_SESSION[$this->sessionKey] as $index => $metric) {
            if (isset($metric['id']) && $metric['id'] === $id) {
                unset($_SESSION[$this->sessionKey][$index]);
                // Переиндексация массива
                $_SESSION[$this->sessionKey] = array_values($_SESSION[$this->sessionKey]);
                return true;
            }
        }

        return false;
    }

    public function clear(): bool
    {
        $_SESSION[$this->sessionKey] = [];
        return true;
    }

    public function getStorageSize(): int
    {
        return strlen(serialize($_SESSION[$this->sessionKey] ?? []));
    }

    public function getEntryCount(): int
    {
        return count($_SESSION[$this->sessionKey] ?? []);
    }

    public function getOldestTimestamp(): ?float
    {
        $metrics = $_SESSION[$this->sessionKey] ?? [];
        if (empty($metrics)) {
            return null;
        }

        $oldest = min(array_column($metrics, 'timestamp'));
        return $oldest;
    }

    public function getNewestTimestamp(): ?float
    {
        $metrics = $_SESSION[$this->sessionKey] ?? [];
        if (empty($metrics)) {
            return null;
        }

        $newest = max(array_column($metrics, 'timestamp'));
        return $newest;
    }
}
