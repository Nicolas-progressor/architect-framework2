<?php

declare(strict_types=1);

namespace app\home\modules\navbar\model;

use Architect\Helpers\Facades\Helper_Html;
use Architect\Services\Mvc\ModelBase;

class navbar extends ModelBase
{
    public function getMenu(): array
    {
        return [
            ['name' => 'Главная', 'url' => Helper_Html::href(''), 'icon' => 'house-fill'],
            ['name' => 'О нас', 'url' => Helper_Html::href('about'), 'icon' => 'people'],
            ['name' => 'Услуги', 'url' => Helper_Html::href('services'), 'icon' => 'briefcase'],
            ['name' => 'Контакты', 'url' => Helper_Html::href('contact'), 'icon' => 'envelope'],
        ];
    }
}
