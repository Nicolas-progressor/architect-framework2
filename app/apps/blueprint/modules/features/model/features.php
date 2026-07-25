<?php

declare(strict_types=1);

namespace app\blueprint\modules\features\model;

use Architect\Services\Mvc\ModelBase;

class features extends ModelBase
{
    public function getPageData(): array
    {
        return [
            'title' => 'Возможности Blueprint',
            'description' => 'Узнайте о всех возможностях шаблонизатора Blueprint'
        ];
    }
    
    public function getBreadcrumbs(): array
    {
        return [
            ['title' => 'Главная', 'url' => '/blueprint/'],
            ['title' => 'Возможности', 'url' => '/blueprint/features']
        ];
    }
    
    public function getFeatures(): array
    {
        return [
            [
                'icon' => 'bi-layout-text-window-reverse',
                'title' => 'Наследование шаблонов',
                'description' => 'Расширяйте базовые шаблоны и переопределяйте блоки для гибкой структуры',
                'code' => "{% extends 'base.blu' %}\n{% block content %}...{% endblock %}"
            ],
            [
                'icon' => 'bi-code-square',
                'title' => 'Синтаксис Blueprint',
                'description' => 'Интуитивно понятный синтаксис, вдохновлённый Blade и Twig',
                'code' => "{{ variable }}\n{% if condition %}...{% endif %}"
            ],
            [
                'icon' => 'bi-arrow-repeat',
                'title' => 'Циклы и условия',
                'description' => 'Полная поддержка циклов for, foreach и условных конструкций',
                'code' => "{% for item in items %}\n  {{ item.name }}\n{% endfor %}"
            ],
            [
                'icon' => 'bi-puzzle',
                'title' => 'Компоненты',
                'description' => 'Создавайте переиспользуемые элементы и виджеты',
                'code' => "{% element 'navbar' %}\n{% widget 'button' %}"
            ],
            [
                'icon' => 'bi-funnel',
                'title' => 'Фильтры',
                'description' => 'Встроенные и пользовательские фильтры для преобразования данных',
                'code' => "{{ text|upper }}\n{{ date|date('Y-m-d') }}"
            ],
            [
                'icon' => 'bi-lightning',
                'title' => 'Кэширование',
                'description' => 'Автоматическое кэширование скомпилированных шаблонов',
                'code' => 'Кэш включён автоматически в production режиме'
            ],
            [
                'icon' => 'bi-braces',
                'title' => 'Макросы',
                'description' => 'Создавайте переиспользуемые фрагменты кода',
                'code' => "{% macro input(name, type='text') %}\n  <input type=\"{{ type }}\" name=\"{{ name }}\">\n{% endmacro %}"
            ],
            [
                'icon' => 'bi-file-earmark-code',
                'title' => 'Raw-блоки',
                'description' => 'Выводите Blueprint-синтаксис как обычный текст',
                'code' => "{% raw %}{{ not parsed }}{% endraw %}"
            ]
        ];
    }
}
