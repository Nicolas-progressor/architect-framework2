<?php

declare(strict_types=1);

namespace app\home\modules\contact\model;

use Architect\Services\Mvc\ModelBase;

class contact extends ModelBase
{
    public function getPageData(): array
    {
        return [
            'title' => 'Контакты',
            'heading' => 'Свяжитесь с нами',
            'description' => 'Мы всегда рады ответить на ваши вопросы'
        ];
    }
    
    public function getBreadcrumbs(): array
    {
        return [
            ['title' => 'Главная', 'url' => '/'],
            ['title' => 'Контакты', 'url' => '/contact']
        ];
    }
}
