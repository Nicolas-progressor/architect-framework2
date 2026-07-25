<?php

declare(strict_types=1);

namespace app\axiom\modules\navbar\model;

use Architect\Services\Mvc\ModelBase;

class navbar extends ModelBase
{
    public function getMenu(): array
    {
        return [
            ['title' => 'Главная', 'url' => '', 'icon' => 'fa-home'],
            ['title' => 'Query Builder', 'url' => 'query', 'icon' => 'fa-database'],
            ['title' => 'Миграции', 'url' => 'migrations', 'icon' => 'fa-migration'],
            ['title' => 'Сущности', 'url' => 'entity', 'icon' => 'fa-cube'],
            ['title' => 'Info (Query)', 'url' => 'info', 'icon' => 'fa-info-circle'],
            ['title' => 'Info (Entity)', 'url' => 'infoentity', 'icon' => 'fa-cubes'],
            ['title' => 'Кэш', 'url' => 'cache', 'icon' => 'fa-cache'],
        ];
    }
}
