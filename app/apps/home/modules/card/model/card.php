<?php

declare(strict_types=1);

namespace app\home\modules\card\model;

use Architect\Services\Mvc\ModelBase;

class card extends ModelBase
{
    public function getCards(): array
    {
        return [
            [
                'title' => 'Быстродействие',
                'description' => 'Высокая производительность и оптимизация',
                'icon' => 'speedometer2'
            ],
            [
                'title' => 'Безопасность',
                'description' => 'Защита от основных уязвимостей',
                'icon' => 'shield-check'
            ],
            [
                'title' => 'Архитектура',
                'description' => 'Современный MVC подход',
                'icon' => 'code-slash'
            ]
        ];
    }
}
