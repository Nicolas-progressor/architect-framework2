<?php

declare(strict_types=1);

namespace app\architect\modules\navbar\widget;

use Architect\Helpers\Facades\Helper_Html;
use pattern\controller;

class navbar extends controller
{
    public function create_app_data(): void
    {
        $this->ext['menu'] = [
            ['name' => 'Главная', 'url' => Helper_Html::href(''), 'icon' => 'house-fill'],
            ['name' => 'Архитектура', 'url' => Helper_Html::href('architecture'), 'icon' => 'diagram-3'],
            ['name' => 'Компоненты', 'url' => Helper_Html::href('components'), 'icon' => 'puzzle'],
            ['name' => 'Маршрутизация', 'url' => Helper_Html::href('routing'), 'icon' => 'signpost'],
            ['name' => 'Шаблоны', 'url' => Helper_Html::href('templates'), 'icon' => 'layout-wtf'],
        ];
    }

    public function create_app_output(): void
    {
        $this->display('navbar');
    }
}
