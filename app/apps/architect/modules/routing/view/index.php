<div class="row">
    <div class="col-12">
        <h1>Маршрутизация в Architect Framework</h1>
        <p class="lead">Маршрутизация в Architect RED 2 основана на URL-сегментах и JSON-конфигурации маршрутов.</p>
        
        <h2>Конфигурация маршрутов</h2>
        <p>Маршруты настраиваются в JSON-файлах:</p>
        <pre><code>{
    "default": "index",
    
    "routes": {
        "home": {
            "module": "home",
            "controller": "home",
            "action": "index"
        },
        "about": {
            "module": "about",
            "controller": "about",
            "action": "index"
        }
    }
}</code></pre>
        
        <h2>Поля маршрута</h2>
        <ul>
            <li><code>module</code> — Имя модуля</li>
            <li><code>controller</code> — Имя контроллера</li>
            <li><code>action</code> — Имя экшена (метода)</li>
            <li><code>template</code> — Шаблон для рендеринга</li>
            <li><code>notemplate</code> — <code>true</code> — без шаблона</li>
            <li><code>app</code> — Приложение (переключить)</li>
            <li><code>var_remap</code> — Перемаппинг параметров URL</li>
        </ul>
        
        <h2>URL-сегменты</h2>
        <p>URL разбивается на сегменты:</p>
        <pre><code>/module/controller/action/param1/param2/...</code></pre>
        
        <h2>Приоритет маршрутов</h2>
        <ol>
            <li>Именованные маршруты (из JSON)</li>
            <li>URL-сегменты (module/controller/action)</li>
            <li>Значения по умолчанию (из router.json)</li>
        </ol>
        
        <h2>Динамические маршруты</h2>
        <p>Используется для перемаппинга сегментов URL в именованные параметры:</p>
        <pre><code>{
    "user_edit": {
        "module": "user",
        "controller": "user",
        "action": "edit",
        "var_remap": ["id", "tab"]
    }
}</code></pre>
        
        <h2>Переключение приложений</h2>
        <p>Маршрут может переключить приложение:</p>
        <pre><code>{
    "admin": {
        "module": "admin",
        "controller": "admin",
        "action": "index",
        "app": "home"
    }
}</code></pre>
    </div>
</div>