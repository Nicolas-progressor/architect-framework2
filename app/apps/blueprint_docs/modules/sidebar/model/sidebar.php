<?php

declare(strict_types=1);

namespace app\blueprint_docs\modules\sidebar\model;

use Architect\Helpers\Facades\Helper_Html;
use Architect\Services\Mvc\ModelBase;

class sidebar extends ModelBase
{
    public function getSections(): array
    {
        return [
            [
                'title' => 'Начало работы',
                'items' => [
                    ['name' => 'Введение', 'url' => Helper_Html::href(''), 'icon' => 'home'],
                    ['name' => 'Установка', 'url' => Helper_Html::href('installation'), 'icon' => 'download'],
                ]
            ],
            [
                'title' => 'Основы',
                'items' => [
                    ['name' => 'Синтаксис', 'url' => Helper_Html::href('syntax'), 'icon' => 'code'],
                    ['name' => 'Переменные', 'url' => Helper_Html::href('variables'), 'icon' => 'variable'],
                    ['name' => 'Фильтры', 'url' => Helper_Html::href('filters'), 'icon' => 'filter'],
                    ['name' => 'Функции', 'url' => Helper_Html::href('functions'), 'icon' => 'function'],
                    ['name' => 'Управляющие конструкции', 'url' => Helper_Html::href('control_structures'), 'icon' => 'branch'],
                ]
            ],
            [
                'title' => 'Продвинутое',
                'items' => [
                    ['name' => 'Наследование', 'url' => Helper_Html::href('inheritance'), 'icon' => 'layers'],
                    ['name' => 'Элементы и виджеты', 'url' => Helper_Html::href('elements'), 'icon' => 'puzzle'],
                    ['name' => 'Расширение', 'url' => Helper_Html::href('extending'), 'icon' => 'plugin'],
                ]
            ],
            [
                'title' => 'Справочник',
                'items' => [
                    ['name' => 'API', 'url' => Helper_Html::href('api'), 'icon' => 'book'],
                    ['name' => 'Интеграции', 'url' => Helper_Html::href('integrations'), 'icon' => 'link'],
                ]
            ]
        ];
    }
}
