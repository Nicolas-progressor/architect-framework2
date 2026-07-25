<?php

namespace Architect\AuthSystem\Events;

/**
 * Полноценный диспетчер событий для системы авторизации.
 * Поддерживает приоритеты, подписку по шаблонам, остановку распространения,
 * интеграцию с системой Statement Architect (опционально).
 */
class AuthEventDispatcher implements EventDispatcherInterface
{
    /**
     * @var array<string, array<int, array{listener: callable, priority: int}>>
     */
    private array $listeners = [];

    /**
     * @var array<string, array{listener: callable, priority: int}> Шаблонные подписчики
     */
    private array $patternListeners = [];

    /**
     * @inheritDoc
     */
    public function subscribe(string $eventName, callable $listener, int $priority = 0): void
    {
        // Если имя события содержит звёздочку - это шаблон
        if (str_contains($eventName, '*')) {
            $this->patternListeners[$eventName][] = [
                'listener' => $listener,
                'priority' => $priority,
            ];
            // Сортируем по приоритету
            usort($this->patternListeners[$eventName], function ($a, $b) {
                return $b['priority'] <=> $a['priority'];
            });
            return;
        }

        if (!isset($this->listeners[$eventName])) {
            $this->listeners[$eventName] = [];
        }

        $this->listeners[$eventName][] = [
            'listener' => $listener,
            'priority' => $priority,
        ];

        // Сортируем по приоритету (высокий приоритет = первый)
        usort($this->listeners[$eventName], function ($a, $b) {
            return $b['priority'] <=> $a['priority'];
        });
    }

    /**
     * @inheritDoc
     */
    public function unsubscribe(string $eventName, callable $listener): void
    {
        if (str_contains($eventName, '*')) {
            if (!isset($this->patternListeners[$eventName])) {
                return;
            }
            foreach ($this->patternListeners[$eventName] as $key => $item) {
                if ($item['listener'] === $listener) {
                    unset($this->patternListeners[$eventName][$key]);
                    $this->patternListeners[$eventName] = array_values($this->patternListeners[$eventName]);
                    break;
                }
            }
            return;
        }

        if (!isset($this->listeners[$eventName])) {
            return;
        }

        foreach ($this->listeners[$eventName] as $key => $item) {
            if ($item['listener'] === $listener) {
                unset($this->listeners[$eventName][$key]);
                // Переиндексируем массив
                $this->listeners[$eventName] = array_values($this->listeners[$eventName]);
                break;
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function dispatch(string $eventName, $payload = null): void
    {
        $event = $payload instanceof AuthEvent ? $payload : null;

        // Собираем всех слушателей
        $listeners = $this->getListenersForEvent($eventName);

        foreach ($listeners as $item) {
            $listener = $item['listener'];
            // Если событие является объектом AuthEvent и распространение остановлено - прерываем
            if ($event && $event->isPropagationStopped()) {
                break;
            }
            call_user_func($listener, $payload);
        }
    }

    /**
     * Получить всех слушателей для события (включая шаблонные).
     *
     * @param string $eventName
     * @return array<array{listener: callable, priority: int}>
     */
    private function getListenersForEvent(string $eventName): array
    {
        $result = [];

        // Прямые слушатели
        if (isset($this->listeners[$eventName])) {
            foreach ($this->listeners[$eventName] as $item) {
                $result[] = $item;
            }
        }

        // Шаблонные слушатели
        foreach ($this->patternListeners as $pattern => $items) {
            if ($this->matchPattern($pattern, $eventName)) {
                foreach ($items as $item) {
                    $result[] = $item;
                }
            }
        }

        // Сортируем по приоритету (высокий приоритет первый)
        usort($result, function ($a, $b) {
            return $b['priority'] <=> $a['priority'];
        });

        return $result;
    }

    /**
     * Проверяет, соответствует ли событие шаблону.
     * Поддерживает только * в конце строки (например, 'auth.*').
     *
     * @param string $pattern
     * @param string $eventName
     * @return bool
     */
    private function matchPattern(string $pattern, string $eventName): bool
    {
        if ($pattern === '*') {
            return true;
        }
        if (str_ends_with($pattern, '*')) {
            $prefix = rtrim($pattern, '*');
            return str_starts_with($eventName, $prefix);
        }
        return $pattern === $eventName;
    }

    /**
     * @inheritDoc
     */
    public function hasListeners(string $eventName): bool
    {
        if (isset($this->listeners[$eventName]) && count($this->listeners[$eventName]) > 0) {
            return true;
        }
        foreach ($this->patternListeners as $pattern => $items) {
            if ($this->matchPattern($pattern, $eventName) && count($items) > 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getListeners(string $eventName): array
    {
        return $this->getListenersForEvent($eventName);
    }

    /**
     * Очистить всех подписчиков события.
     *
     * @param string $eventName
     * @return void
     */
    public function clearListeners(string $eventName): void
    {
        unset($this->listeners[$eventName]);
        // Удалить шаблонные? Пока не будем.
    }

    /**
     * Получить список всех событий, на которые есть подписчики.
     *
     * @return array
     */
    public function getRegisteredEvents(): array
    {
        $events = array_keys($this->listeners);
        $patterns = array_keys($this->patternListeners);
        return array_unique(array_merge($events, $patterns));
    }

    /**
     * Интеграция с системой Statement Architect.
     * Регистрирует обработчик, который будет вызывать dispatch при срабатывании statement.
     *
     * @param \Architect\Core\Statement\StatementManager $statementManager
     * @return void
     */
    public function integrateWithStatement($statementManager): void
    {
        // Если StatementManager поддерживает подписку на события
        if (method_exists($statementManager, 'on')) {
            $statementManager->on('auth.*', function ($payload) {
                $this->dispatch('auth.*', $payload);
            });
        }
    }
}