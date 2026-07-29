<?php

declare(strict_types=1);

namespace app\axiom\modules\home\model;

use Architect\Services\Mvc\ModelBase;

class home extends ModelBase
{
    public function getFeatures(): array
    {
        return [
            [
                'icon' => 'fa-code',
                'title' => 'Query Builder',
                'description' => 'Мощный построитель запросов с интуитивным API для работы с базой данных.',
                'link' => 'query',
                'color' => 'bg-blue-500',
            ],
            [
                'icon' => 'fa-exchange-alt',
                'title' => 'Миграции',
                'description' => 'Управление схемой базы данных через код с поддержкой версионирования.',
                'link' => 'migrations',
                'color' => 'bg-green-500',
            ],
            [
                'icon' => 'fa-cube',
                'title' => 'Entity',
                'description' => 'Объектно-реляционное отображение с поддержкой атрибутов PHP 8+.',
                'link' => 'entity',
                'color' => 'bg-purple-500',
            ],
            [
                'icon' => 'fa-bolt',
                'title' => 'Cache',
                'description' => 'Кэширование запросов с поддержкой различных драйверов.',
                'link' => 'cache',
                'color' => 'bg-orange-500',
            ],
        ];
    }

    public function getStats(): array
    {
        return [
            'queries' => 0,
            'migrations' => 0,
        ];
    }
}
