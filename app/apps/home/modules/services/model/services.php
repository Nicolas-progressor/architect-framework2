<?php

declare(strict_types=1);

namespace app\home\modules\services\model;

use Architect\Services\Mvc\ModelBase;

class services extends ModelBase
{
    public function getPageData(): array
    {
        return [
            'title' => 'Услуги',
            'heading' => 'Наши услуги',
            'description' => 'Мы предлагаем широкий спектр услуг',
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            ['title' => 'Главная', 'url' => '/'],
            ['title' => 'Услуги', 'url' => '/services'],
        ];
    }
}
