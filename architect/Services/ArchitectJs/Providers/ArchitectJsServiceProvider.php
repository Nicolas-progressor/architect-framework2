<?php

declare(strict_types=1);

namespace Architect\Services\ArchitectJs\Providers;

use Architect\Contracts\Core\ContainerInterface;
use Architect\Contracts\ServiceProviderInterface;

class ArchitectJsServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerInterface $container): void
    {
        // архитектор может зарегистрировать сервисы здесь
    }

    public function boot(ContainerInterface $container): void
    {
        // Добавляем architect.js в AssetsHelper при его наличии
        if ($container->has('helper.assets')) {
            $assets = $container->get('helper.assets');

            $base = '/assets/architect-js/src/';
            $assets->js($base . 'core.js');
            $assets->js($base . 'state.js');
            $assets->js($base . 'http.js');
            $assets->js($base . 'router.js');
            $assets->js($base . 'component.js');
            $assets->js($base . 'app.js');
        }

        // Передаём CSRF-токен в JavaScript, если доступен
        $config = [
            'csrf' => defined('CSRF_TOKEN') ? CSRF_TOKEN : null,
            'locale' => defined('APP_LOCALE') ? APP_LOCALE : 'ru',
        ];

        $configJson = json_encode(array_filter($config));

        // Регистрируем хук для инжекта конфига в шапку шаблона
        $container->set('architect-js.config', function () use ($configJson) {
            return "<script>window.ARCHITECT_CONFIG = {$configJson};</script>";
        });
    }
}
