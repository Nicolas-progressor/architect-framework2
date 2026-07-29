<?php

declare(strict_types=1);

namespace app\blueprint\modules\docs\model;

use Architect\Services\Mvc\ModelBase;

class docs extends ModelBase
{
    public function getPageData(): array
    {
        return [
            'title' => 'Документация Blueprint',
            'description' => 'Полная документация по синтаксису и возможностям шаблонизатора Blueprint',
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            ['title' => 'Главная', 'url' => '/blueprint/'],
            ['title' => 'Документация', 'url' => '/blueprint/docs'],
        ];
    }

    public function getDocsSections(): array
    {
        return [
            [
                'id' => 'variables',
                'title' => 'Переменные',
                'icon' => 'bi-braces',
                'description' => 'Доступ к переменным осуществляется через контекст. Поддерживается доступ к вложенным свойствам и массивам.',
                'example' => "{{ username }}\n{{ user.name }}\n{{ items[0] }}",
                'result' => 'Выводит значение переменной',
            ],
            [
                'id' => 'output',
                'title' => 'Вывод',
                'icon' => 'bi-code-slash',
                'description' => 'По умолчанию весь вывод экранируется для безопасности. Используйте raw блок для сырого вывода.',
                'example' => "{{ variable }}\n{% raw %}{{ raw }}{% endraw %}",
                'result' => 'Экранированный и сырой вывод',
            ],
            [
                'id' => 'filters',
                'title' => 'Фильтры',
                'icon' => 'bi-funnel',
                'description' => 'Фильтры применяются через символ | и позволяют трансформировать данные.',
                'example' => "{{ name|upper }}\n{{ text|trim|truncate(10) }}",
                'result' => 'JOHN, обрезанный текст',
            ],
            [
                'id' => 'conditions',
                'title' => 'Условия',
                'icon' => 'bi-diagram-3',
                'description' => 'Полная поддержка if/elseif/else с операторами сравнения и логическими операторами.',
                'example' => "{% if age >= 18 %}\n  Взрослый\n{% else %}\n  Ребёнок\n{% endif %}",
                'result' => 'Условный вывод',
            ],
            [
                'id' => 'loops',
                'title' => 'Циклы',
                'icon' => 'bi-arrow-repeat',
                'description' => 'Поддержка for и foreach с дополнительными переменными loop для отслеживания итерации.',
                'example' => "{% for item in items %}\n  {{ loop.index }}. {{ item.name }}\n{% endfor %}",
                'result' => '1. Элемент 1\n2. Элемент 2',
            ],
            [
                'id' => 'inheritance',
                'title' => 'Наследование',
                'icon' => 'bi-layers',
                'description' => 'Расширяйте базовые шаблоны и переопределяйте блоки. Используйте parent() для вызова родительского контента.',
                'example' => "{% extends 'base' %}\n{% block content %}{% endblock %}",
                'result' => 'Иерархия шаблонов',
            ],
            [
                'id' => 'macros',
                'title' => 'Макросы',
                'icon' => 'bi-gear',
                'description' => 'Создавайте переиспользуемые фрагменты с параметрами по умолчанию.',
                'example' => "{% macro input(name, type='text') %}\n  <input type=\"{{ type }}\" name=\"{{ name }}\">\n{% endmacro %}",
                'result' => 'Переиспользуемые компоненты',
            ],
            [
                'id' => 'elements',
                'title' => 'Элементы и виджеты',
                'icon' => 'bi-grid',
                'description' => 'Подключайте готовые компоненты через элементы и виджеты.',
                'example' => "{% element 'navbar' %}\n{% widget 'button' %}",
                'result' => 'Подключение компонентов',
            ],
        ];
    }
}
