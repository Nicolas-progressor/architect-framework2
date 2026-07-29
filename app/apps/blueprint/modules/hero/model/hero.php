<?php

declare(strict_types=1);

namespace app\blueprint\modules\hero\model;

use Architect\Services\Mvc\ModelBase;

class hero extends ModelBase
{
    public function getFeatures(): array
    {
        return [
            ['title' => 'Наследование шаблонов', 'desc' => 'Расширяйте базовые шаблоны и переопределяйте блоки', 'icon' => 'layers'],
            ['title' => 'Блоки и секции', 'desc' => 'Гибкая система блоков для организации контента', 'icon' => 'grid'],
            ['title' => 'Фильтры и функции', 'desc' => 'Богатый набор встроенных фильтров и функций', 'icon' => 'funnel'],
            ['title' => 'Макросы', 'desc' => 'Создавайте переиспользуемые компоненты', 'icon' => 'code-slash'],
            ['title' => 'Элементы и виджеты', 'desc' => 'Встроенная поддержка компонентов', 'icon' => 'palette'],
            ['title' => 'Кэширование', 'desc' => 'Автоматическое кэширование скомпилированных шаблонов', 'icon' => 'HDD'],
        ];
    }
}
