<?php

declare(strict_types=1);

namespace app\modules\pages;

/**
 * Bootstrap для общего модуля pages
 *
 * Показывает как управлять шаблоном для общих модулей
 */
class modulebootstrap
{
    public function method_core_post_load(): void
    {
        // Для общего модуля по умолчанию шаблон отключён
        // Здесь можно включить его при необходимости
        // Пример: $this->enableTemplateForSpecificPages();
    }

    /**
     * Включить шаблон для определённых страниц
     */
    private function enableTemplateForSpecificPages(): void
    {
        $container = \Architect\Core\Container::getInstance();
        $router = $container->get('router');
        $template = $container->get('template');

        // Для страниц с шаблоном - включаем его
        if ($router->segment(2) === 'withtemplate') {
            $template->loadFromApp('bootstrap');
        }
    }
}
