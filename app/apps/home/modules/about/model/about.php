<?php

declare(strict_types=1);

namespace app\home\modules\about\model;

use Architect\Services\Mvc\ModelBase;

class about extends ModelBase
{
    public function getPageData(): array
    {
        return [
            'title' => 'О нас',
            'heading' => 'О нашей компании',
            'description' => 'Мы работаем для вас с 2020 года'
        ];
    }
    
    public function getBreadcrumbs(): array
    {
        return [
            ['title' => 'Главная', 'url' => '/'],
            ['title' => 'О нас', 'url' => '/about']
        ];
    }
}
