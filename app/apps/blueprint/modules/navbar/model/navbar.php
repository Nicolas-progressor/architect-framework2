<?php

declare(strict_types=1);

namespace app\blueprint\modules\navbar\model;

use Architect\Services\Mvc\ModelBase;
use Architect\Helpers\Facades\Helper_Html;

class navbar extends ModelBase
{
    public function getMenu(): array
    {
        return [
            ['name' => 'Главная', 'url' => Helper_Html::href(''), 'icon' => 'house-fill'],
            ['name' => 'Возможности', 'url' => Helper_Html::href('features'), 'icon' => 'stars'],
            ['name' => 'Документация', 'url' => Helper_Html::href('docs'), 'icon' => 'book'],
            ['name' => 'Контакты', 'url' => Helper_Html::href('contact'), 'icon' => 'envelope']
        ];
    }
}
