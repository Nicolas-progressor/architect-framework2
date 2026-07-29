<?php

declare(strict_types=1);

namespace Architect\Services\Event\Providers;

use Architect\Contracts\Core\ContainerInterface;
use Architect\Contracts\Event\EventDispatcherInterface;
use Architect\Services\Event\EventManager;
use Architect\Support\AbstractServiceProvider;

class EventServiceProvider extends AbstractServiceProvider
{
    public function register(ContainerInterface $container): void
    {
        $this->registerSingleton($container, EventManager::class, function () {
            return new EventManager();
        });

        $this->registerSingleton($container, EventDispatcherInterface::class, function ($c) {
            return $c->get(EventManager::class);
        });
    }

    public function boot(ContainerInterface $container): void
    {
        $eventManager = $container->get(EventManager::class);

        if ($container->has('config.events')) {
            $config = $container->get('config.events');
            $listeners = $config instanceof \ArrayAccess ? $config : ($config['events'] ?? []);

            foreach ($listeners as $event => $eventListeners) {
                foreach ($eventListeners as $listener) {
                    $priority = is_array($listener) ? ($listener['priority'] ?? 0) : 0;
                    $callable = is_array($listener) ? $listener['handler'] : $listener;
                    $eventManager->listen($event, $callable, $priority);
                }
            }
        }
    }
}
