<?php

declare(strict_types=1);

namespace app\blueprint\modules\home\model;

use Architect\Services\Mvc\ModelBase;

class home extends ModelBase
{
    public function getPageData(): array
    {
        return [
            'title' => 'Blueprint - Современный шаблонизатор',
            'description' => 'Демонстрация шаблонизатора Blueprint для Architect Framework'
        ];
    }
    
    public function getBreadcrumbs(): array
    {
        return [
            ['title' => 'Главная', 'url' => '/']
        ];
    }
}
