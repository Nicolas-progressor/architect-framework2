<?php

declare(strict_types=1);

namespace Architect\Services\Event\Providers;

use Architect\Contracts\Event\EventDispatcherInterface;
use Architect\Services\Event\EventManager;

class EventServiceProvider extends \Architect\Core\ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EventManager::class, function () {
            return new EventManager();
        });

        $this->app->singleton(EventDispatcherInterface::class, function ($app) {
            return $app->make(EventManager::class);
        });
    }

    public function boot(): void
    {
        $eventManager = $this->app->make(EventManager::class);

        if (isset($this->config['events'])) {
            foreach ($this->config['events'] as $event => $listeners) {
                foreach ($listeners as $listener) {
                    $priority = is_array($listener) ? ($listener['priority'] ?? 0) : 0;
                    $callable = is_array($listener) ? $listener['handler'] : $listener;
                    $eventManager->listen($event, $callable, $priority);
                }
            }
        }
    }
}
