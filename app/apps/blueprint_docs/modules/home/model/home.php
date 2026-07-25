<?php

declare(strict_types=1);

namespace app\blueprint_docs\modules\home\model;

use Architect\Services\Mvc\ModelBase;

class home extends ModelBase
{
    public function getPageData(): array
    {
        return [
            'title' => 'Blueprint — Шаблонизатор для PHP',
            'subtitle' => 'Современный, быстрый и расширяемый шаблонизатор с Blade/Twig-подобным синтаксисом',
            'description' => 'Blueprint компилирует шаблоны в чистый PHP-код, обеспечивая максимальную производительность. Поддерживает наследование шаблонов, фильтры, функции, элементы и виджеты.'
        ];
    }
    
    public function getFeatures(): array
    {
        return [
            [
                'icon' => 'rocket',
                'title' => 'Высокая производительность',
                'description' => 'Компиляция в чистый PHP-код и кэширование обеспечивают максимальную скорость работы'
            ],
            [
                'icon' => 'layers',
                'title' => 'Наследование шаблонов',
                'description' => 'Мощная система блоков, extends, include и parent() для гибкой структуры'
            ],
            [
                'icon' => 'filter',
                'title' => '40+ фильтров',
                'description' => 'Богатый набор встроенных фильтров для преобразования данных'
            ],
            [
                'icon' => 'puzzle',
                'title' => 'Элементы и виджеты',
                'description' => 'Переиспользуемые компоненты с интеграцией MVC-виджетов'
            ],
            [
                'icon' => 'shield',
                'title' => 'Безопасность',
                'description' => 'Автоматическое экранирование вывода для защиты от XSS-атак'
            ],
            [
                'icon' => 'plugin',
                'title' => 'Расширяемость',
                'description' => 'Создавайте собственные фильтры, функции и расширения'
            ]
        ];
    }
}
