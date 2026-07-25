<?php

declare(strict_types=1);

namespace Architect\Services\Form;

use Architect\Services\Form\Interfaces\SessionInterface;

/**
 * Class NativeSession
 *
 * Реализация SessionInterface для работы с PHP-сессией.
 */
class NativeSession implements SessionInterface
{
    /**
     * Конструктор
     */
    public function __construct()
    {
        $this->startIfNeeded();
    }

    /**
     * Запустить сессию если нужно
     *
     * @return void
     */
    private function startIfNeeded(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Получить значение из сессии
     *
     * @param string $key Ключ
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Установить значение в сессию
     *
     * @param string $key Ключ
     * @param mixed $value Значение
     * @return void
     */
    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Удалить значение из сессии
     *
     * @param string $key Ключ
     * @return void
     */
    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Проверить наличие ключа
     *
     * @param string $key Ключ
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }
}
