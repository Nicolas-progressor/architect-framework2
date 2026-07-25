<?php

declare(strict_types=1);

namespace Architect\Services\Form\Interfaces;

/**
 * Interface RequestInterface
 *
 * Абстракция для работы с HTTP-запросом.
 * Позволяет тестировать код без реального HTTP-запроса.
 */
interface RequestInterface
{
    /**
     * Получить данные из POST
     *
     * @param string|null $key Ключ (null для всех данных)
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    public function getPost(?string $key = null, mixed $default = null): mixed;

    /**
     * Получить данные из GET
     *
     * @param string|null $key Ключ (null для всех данных)
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    public function getGet(?string $key = null, mixed $default = null): mixed;

    /**
     * Проверить, был ли POST-запрос
     *
     * @return bool
     */
    public function isPost(): bool;

    /**
     * Проверить, был ли GET-запрос
     *
     * @return bool
     */
    public function isGet(): bool;

    /**
     * Получить сессию
     *
     * @return SessionInterface
     */
    public function getSession(): SessionInterface;

    /**
     * Получить все данные запроса
     *
     * @return array
     */
    public function all(): array;
}
