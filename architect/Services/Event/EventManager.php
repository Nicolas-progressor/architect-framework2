<?php

declare(strict_types=1);

namespace Architect\Services\Event;

use Architect\Contracts\Event\EventDispatcherInterface;

class EventManager implements EventDispatcherInterface
{
    private array $listeners = [];
    private array $patternListeners = [];
    private array $filters = [];
    private array $patternFilters = [];

    public function listen(string $event, callable $listener, int $priority = 0): void
    {
        if (str_contains($event, '*')) {
            $this->patternListeners[$event][] = ['listener' => $listener, 'priority' => $priority];
            $this->sortByPriority($this->patternListeners[$event]);
            return;
        }

        $this->listeners[$event][] = ['listener' => $listener, 'priority' => $priority];
        $this->sortByPriority($this->listeners[$event]);
    }

    public function dispatch(string $event, mixed $payload = null): mixed
    {
        $eventObj = $payload instanceof Event ? $payload : new Event($event, $payload);

        $allListeners = $this->getListenersForEvent($event);

        foreach ($allListeners as $item) {
            if ($eventObj->isPropagationStopped()) {
                break;
            }
            call_user_func($item['listener'], $eventObj);
        }

        return $eventObj->getPayload();
    }

    public function hasListeners(string $event): bool
    {
        if (!empty($this->listeners[$event])) {
            return true;
        }
        foreach ($this->patternListeners as $pattern => $items) {
            if (!empty($items) && $this->matchPattern($pattern, $event)) {
                return true;
            }
        }
        return false;
    }

    public function subscribe(string $event, callable $listener, int $priority = 0): void
    {
        $this->listen($event, $listener, $priority);
    }

    public function unsubscribe(string $event, callable $listener): void
    {
        if (str_contains($event, '*')) {
            if (isset($this->patternListeners[$event])) {
                foreach ($this->patternListeners[$event] as $key => $item) {
                    if ($item['listener'] === $listener) {
                        unset($this->patternListeners[$event][$key]);
                        $this->patternListeners[$event] = array_values($this->patternListeners[$event]);
                        break;
                    }
                }
            }
            return;
        }

        if (!isset($this->listeners[$event])) {
            return;
        }

        foreach ($this->listeners[$event] as $key => $item) {
            if ($item['listener'] === $listener) {
                unset($this->listeners[$event][$key]);
                $this->listeners[$event] = array_values($this->listeners[$event]);
                break;
            }
        }
    }

    public function getListeners(?string $event = null): array
    {
        if ($event === null) {
            return $this->listeners;
        }
        return $this->getListenersForEvent($event);
    }

    public function clearListeners(?string $event = null): void
    {
        if ($event === null) {
            $this->listeners = [];
            $this->patternListeners = [];
            $this->filters = [];
            $this->patternFilters = [];
            return;
        }

        unset($this->listeners[$event]);
        unset($this->patternListeners[$event]);
        unset($this->filters[$event]);
        unset($this->patternFilters[$event]);
    }

    public function getRegisteredEvents(): array
    {
        return array_unique(array_merge(
            array_keys($this->listeners),
            array_keys($this->patternListeners),
            array_keys($this->filters),
            array_keys($this->patternFilters),
        ));
    }

    public function addFilter(string $hook, callable $callback, int $priority = 10): void
    {
        if (str_contains($hook, '*')) {
            $this->patternFilters[$hook][] = ['callback' => $callback, 'priority' => $priority];
            $this->sortByPriority($this->patternFilters[$hook]);
            return;
        }

        $this->filters[$hook][] = ['callback' => $callback, 'priority' => $priority];
        $this->sortByPriority($this->filters[$hook]);
    }

    public function removeFilter(string $hook, callable $callback): void
    {
        if (str_contains($hook, '*')) {
            if (isset($this->patternFilters[$hook])) {
                foreach ($this->patternFilters[$hook] as $key => $item) {
                    if ($item['callback'] === $callback) {
                        unset($this->patternFilters[$hook][$key]);
                        $this->patternFilters[$hook] = array_values($this->patternFilters[$hook]);
                        break;
                    }
                }
            }
            return;
        }

        if (!isset($this->filters[$hook])) {
            return;
        }

        foreach ($this->filters[$hook] as $key => $item) {
            if ($item['callback'] === $callback) {
                unset($this->filters[$hook][$key]);
                $this->filters[$hook] = array_values($this->filters[$hook]);
                break;
            }
        }
    }

    public function hasFilter(string $hook): bool
    {
        if (!empty($this->filters[$hook])) {
            return true;
        }
        foreach ($this->patternFilters as $pattern => $items) {
            if (!empty($items) && $this->matchPattern($pattern, $hook)) {
                return true;
            }
        }
        return false;
    }

    public function applyFilters(string $hook, mixed $value, mixed ...$args): mixed
    {
        $callbacks = $this->getFiltersForHook($hook);

        foreach ($callbacks as $item) {
            $value = call_user_func($item['callback'], $value, ...$args);
        }

        return $value;
    }

    private function getListenersForEvent(string $event): array
    {
        $result = [];

        if (isset($this->listeners[$event])) {
            foreach ($this->listeners[$event] as $item) {
                $result[] = $item;
            }
        }

        foreach ($this->patternListeners as $pattern => $items) {
            if ($this->matchPattern($pattern, $event)) {
                foreach ($items as $item) {
                    $result[] = $item;
                }
            }
        }

        $this->sortByPriority($result);

        return $result;
    }

    private function getFiltersForHook(string $hook): array
    {
        $result = [];

        if (isset($this->filters[$hook])) {
            foreach ($this->filters[$hook] as $item) {
                $result[] = $item;
            }
        }

        foreach ($this->patternFilters as $pattern => $items) {
            if ($this->matchPattern($pattern, $hook)) {
                foreach ($items as $item) {
                    $result[] = $item;
                }
            }
        }

        $this->sortByPriority($result);

        return $result;
    }

    private function matchPattern(string $pattern, string $name): bool
    {
        if ($pattern === '*') {
            return true;
        }

        $prefix = rtrim($pattern, '*');
        if ($prefix !== $pattern) {
            return str_starts_with($name, $prefix);
        }

        $suffix = ltrim($pattern, '*');
        if ($suffix !== $pattern) {
            return str_ends_with($name, $suffix);
        }

        return $pattern === $name;
    }

    private function sortByPriority(array &$items): void
    {
        usort($items, fn($a, $b) => $b['priority'] <=> $a['priority']);
    }
}
