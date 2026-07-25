<div class="row">
    <div class="col-12">
        <h1>Компоненты Architect Framework</h1>
        <p class="lead">Architect RED 2 состоит из множества компонентов, обеспечивающих гибкость и расширяемость фреймворка.</p>
        
        <h2>Основные компоненты</h2>
        
        <div class="card mb-3">
            <div class="card-header">
                <h3>MVC (Model-View-Controller)</h3>
            </div>
            <div class="card-body">
                <p>Классическая MVC-архитектура с расширениями:</p>
                <ul>
                    <li><strong>Controller</strong> — Базовый контроллер с тремя стадиями жизненного цикла: <code>_app_load</code>, <code>_app_data</code>, <code>_app_output</code></li>
                    <li><strong>Model</strong> — Сервис моделей и базовый класс ModelBase</li>
                    <li><strong>View</strong> — Сервис представлений</li>
                </ul>
                <p>Контроллеры могут использовать виджеты для создания переиспользуемых компонентов интерфейса.</p>
            </div>
        </div>
        
        <div class="card mb-3">
            <div class="card-header">
                <h3>Routing (Маршрутизация)</h3>
            </div>
            <div class="card-body">
                <p>Маршрутизация основана на URL-сегментах и JSON-конфигурации:</p>
                <ul>
                    <li>Именованные маршруты в JSON-файлах</li>
                    <li>Поддержка параметров URL и их перемаппинга</li>
                    <li>Переключение приложений через маршруты</li>
                    <li>Кастомные 404 страницы</li>
                </ul>
            </div>
        </div>
        
        <div class="card mb-3">
            <div class="card-header">
                <h3>Template (Шаблонизатор)</h3>
            </div>
            <div class="card-body">
                <p>Система шаблонов с поддержкой виджетов:</p>
                <ul>
                    <li>Макеты (layouts) для представлений</li>
                    <li>Виджеты — переиспользуемые компоненты интерфейса</li>
                    <li>Routed Elements — виджеты для конкретных страниц</li>
                    <li>Blueprint — современный шаблонизатор с синтаксисом, похожим на Blade/Twig</li>
                </ul>
            </div>
        </div>
        
        <div class="card mb-3">
            <div class="card-header">
                <h3>Units (Юниты)</h3>
            </div>
            <div class="card-body">
                <p>Вспомогательные классы через Composer:</p>
                <ul>
                    <li>Breadcrumbs — хлебные крошки</li>
                    <li>Title — заголовок страницы</li>
                    <li>Html — хелперы для HTML</li>
                    <li>Query — построитель запросов</li>
                </ul>
            </div>
        </div>
        
        <div class="card mb-3">
            <div class="card-header">
                <h3>Services (Сервисы)</h3>
            </div>
            <div class="card-body">
                <p>Основные сервисы фреймворка:</p>
                <ul>
                    <li>Config — конфигурация приложения</li>
                    <li>Logger — логирование</li>
                    <li>Debug — отладка и профилирование</li>
                    <li>Console — командная строка</li>
                    <li>I18n — интернационализация</li>
                </ul>
            </div>
        </div>
    </div>
</div>