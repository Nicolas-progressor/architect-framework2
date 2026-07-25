<?php

declare(strict_types=1);

namespace app\home\modules\home\model;

use Architect\Services\Mvc\ModelBase;

class home extends ModelBase
{
    public function getPageData(): array
    {
        return [
            'title' => 'Главная страница',
            'heading' => 'Добро пожаловать',
            'description' => 'Это приложение на базе Architect Framework'
        ];
    }
    
    public function getBreadcrumbs(): array
    {
        return [
            ['title' => 'Главная', 'url' => '/']
        ];
    }
}
