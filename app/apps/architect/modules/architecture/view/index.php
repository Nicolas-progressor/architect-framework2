<div class="row">
    <div class="col-12">
        <h1>Архитектура Architect Framework</h1>
        <p class="lead">Architect RED 2 использует statement-based MVC архитектуру с контейнером зависимостей.</p>
        
        <h2>Ядро фреймворка</h2>
        <p>Ядро Architect RED 2 состоит из нескольких ключевых компонентов:</p>
        <ul>
            <li><strong>Framework</strong> — Основной класс приложения</li>
            <li><strong>Container</strong> — Контейнер зависимостей (DIC)</li>
            <li><strong>Statement</strong> — Система statement-ов (жизненный цикл)</li>
            <li><strong>EnvironmentManager</strong> — Управление окружением</li>
        </ul>
        
        <h2>Контейнер зависимостей</h2>
        <p>Контейнер реализует паттерн Singleton и управляет сервисами приложения:</p>
        <pre><code>$container = Container::getInstance();

// Получить сервис (синглтон)
$service = $container->get('router');

// Зарегистрировать сервис
$container->factory('my_service', function($c) {
    return new MyService($c->get('config'));
});</code></pre>
        
        <h2>Statement-ы (Жизненный цикл)</h2>
        <p>Statement-ы управляют жизненным циклом приложения. Каждый statement представляет определённый этап работы:</p>
        <ul>
            <li><code>core_preinit</code> — Предварительная инициализация</li>
            <li><code>core_init</code> — Инициализация ядра, регистрация сервисов</li>
            <li><code>core_load</code> — Загрузка приложения, роутинг</li>
            <li><code>core_post_load</code> — После загрузки</li>
            <li><code>app_load</code> — Загрузка данных модуля</li>
            <li><code>app_data</code> — Обработка данных (модель)</li>
            <li><code>app_output</code> — Вывод (контроллер)</li>
            <li><code>render</code> — Рендеринг представления</li>
        </ul>
        
        <h2>Bootstrap</h2>
        <p>Файл <code>architect/bootstrap.php</code> инициализирует фреймворк:</p>
        <pre><code>&lt;?php
// architect/bootstrap.php

// Автозагрузка Composer
require_once ROOT_DIR . 'vendor/autoload.php';

// Подключение ядра
require_once ARC_DIR . 'Core/Container.php';
require_once ARC_DIR . 'Core/Statement.php';
require_once ARC_DIR . 'Core/Framework.php';
require_once ARC_DIR . 'Core/EnvironmentManager.php';

// Инициализация EnvironmentManager (до контейнера!)
$envManager = EnvironmentManager::getInstance();

// Определение констант
define('APP_ENV', $envManager-&gt;getEnvironment());
define('APP_DEBUG', $envManager-&gt;isDevelopment() || $envManager-&gt;isTesting());

// Подключение сервисов
require_once ARC_DIR . 'Support/ServiceProvider.php';
require_once ARC_DIR . 'Services/Config/Config.php';
// ... остальные сервисы

// Создание контейнера и запуск
$container = Container::getInstance();
$provider = new ServiceProvider($container);
$provider-&gt;register();

Framework::run();
?&gt;</code></pre>
    </div>
</div>