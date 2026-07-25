<?php

declare(strict_types=1);

namespace Architect\Services\Form\Interfaces;

/**
 * Interface SessionInterface
 * 
 * Абстракция для работы с сессией.
 * Позволяет тестировать код без реальной сессии.
 */
interface SessionInterface
{
    /**
     * Получить значение из сессии
     * 
     * @param string $key Ключ
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Установить значение в сессию
     * 
     * @param string $key Ключ
     * @param mixed $value Значение
     * @return void
     */
    public function set(string $key, mixed $value): void;

    /**
     * Удалить значение из сессии
     * 
     * @param string $key Ключ
     * @return void
     */
    public function remove(string $key): void;

    /**
     * Проверить наличие ключа
     * 
     * @param string $key Ключ
     * @return bool
     */
    public function has(string $key): bool;
}
