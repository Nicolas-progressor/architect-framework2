<div class="row">
    <div class="col-12">
        <h1>Шаблоны в Architect Framework</h1>
        <p class="lead">Template-сервис управляет макетами (layouts) для представлений.</p>
        
        <h2>Концепция</h2>
        <p>Шаблонизатор позволяет обернуть представление в общий макет:</p>
        <pre><code>┌─────────────────────────────┐
│         HEADER              │
├─────────────────────────────┤
│                             │
│      CONTENT (view)        │
│                             │
├─────────────────────────────┤
│         FOOTER              │
└─────────────────────────────┘</code></pre>
        
        <h2>Структура шаблонов</h2>
        <p>Шаблоны могут быть общими и для конкретного приложения:</p>
        <pre><code>app/template/                    # Общие шаблоны
├── bootstrap/
│   ├── template.php
│   └── elements.json
└── admin/
    └── template.php

app/apps/home/template/          # Шаблоны приложения home
└── bootstrap/
    ├── template.php
    └── elements.json</code></pre>
        
        <h2>Elements (Элементы)</h2>
        <p>Elements.json конфигурирует виджеты элементов шаблона:</p>
        <pre><code>{
    "navbar": {
        "type": "widget",
        "module": "home",
        "controller": "navbar",
        "action": "create"
    },
    "breadcrumbs": {
        "type": "widget",
        "module": "breadcrumbs",
        "controller": "breadcrumbs",
        "action": "create"
    },
    "footer": {
        "type": "widget",
        "module": "home",
        "controller": "footer",
        "action": "create"
    }
}</code></pre>
        
        <h2>Виджеты</h2>
        <p>Виджеты — это переиспользуемые компоненты интерфейса:</p>
        <pre><code>&lt;?php $this-&gt;element('navbar'); ?&gt;</code></pre>
        
        <h2>Routed Elements</h2>
        <p>Routed Elements — виджеты для конкретных страниц:</p>
        <pre><code>app/apps/home/template/bootstrap/elements/
├── index.json             # Элементы для страницы index
├── home_index.json        # Элементы для модуля home, контроллера home, action index
└── about.json             # Элементы для страницы about</code></pre>
        
        <h2>Blueprint</h2>
        <p>Blueprint — современный шаблонизатор с поддержкой .blu файлов и синтаксисом, похожим на Blade/Twig:</p>
        <pre><code>{# app/apps/home/template/bootstrap/index.blu #}
<!DOCTYPE html>
<html>
<head>
    <title>{{ title }}</title>
</head>
<body>
    <h1>{{ greeting }}</h1>
    
    {% if items %}
    <ul>
    {% for item in items %}
        <li>{{ item.name }}</li>
    {% endfor %}
    </ul>
    {% endif %}
</body>
</html></code></pre>
    </div>
</div>