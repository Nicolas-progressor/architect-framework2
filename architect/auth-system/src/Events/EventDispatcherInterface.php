<?php

namespace Architect\AuthSystem\Events;

/**
 * Интерфейс диспетчера событий для системы авторизации.
 * Позволяет подписываться на события и генерировать их.
 */
interface EventDispatcherInterface
{
    /**
     * Подписаться на событие.
     *
     * @param string $eventName Имя события (например, 'auth.login')
     * @param callable $listener Функция-обработчик
     * @param int $priority Приоритет (чем выше, тем раньше выполнится)
     * @return void
     */
    public function subscribe(string $eventName, callable $listener, int $priority = 0): void;

    /**
     * Отписаться от события.
     *
     * @param string $eventName
     * @param callable $listener
     * @return void
     */
    public function unsubscribe(string $eventName, callable $listener): void;

    /**
     * Диспетчеризация события.
     *
     * @param string $eventName
     * @param mixed $payload Данные события
     * @return void
     */
    public function dispatch(string $eventName, $payload = null): void;

    /**
     * Проверить, есть ли подписчики на событие.
     *
     * @param string $eventName
     * @return bool
     */
    public function hasListeners(string $eventName): bool;

    /**
     * Получить список подписчиков события.
     *
     * @param string $eventName
     * @return array
     */
    public function getListeners(string $eventName): array;
}