<?php

declare(strict_types=1);

namespace app\modules\debugtest\controller;

use pattern\controller;

class debugtest extends controller
{
    public function index_app_load(): void {
        $language = $this->get('language');
        $language->file('debugtest', 'modules');
    }
    
    public function index_app_data(): void {
        // Тестируем DebugDataCollector
        
        // 1. Сообщения разных уровней
        \Architect\Support\Debug::addMessage('test', 'Обычное сообщение', 'info', ['key' => 'value']);
        \Architect\Support\Debug::addMessage('test', 'Сообщение с данными', 'debug', ['user' => ['id' => 1, 'name' => 'Test']]);
        \Architect\Support\Debug::addMessage('warning', 'Предупреждение!', 'warning', ['code' => 404]);
        \Architect\Support\Debug::addMessage('error', 'Ошибка тестовая', 'error', ['exception' => 'TestException']);
        
        // 2. Таймеры
        \Architect\Support\Debug::startTimer('query_execution', 'database');
        usleep(50000); // 50ms
        $duration = \Architect\Support\Debug::stopTimer('query_execution');
        
        \Architect\Support\Debug::startTimer('api_call', 'external');
        usleep(100000); // 100ms
        \Architect\Support\Debug::stopTimer('api_call');
        
        // 3. Данные
        \Architect\Support\Debug::addData('results', [
            'users' => [
                ['id' => 1, 'name' => 'Alice', 'email' => 'alice@test.com'],
                ['id' => 2, 'name' => 'Bob', 'email' => 'bob@test.com'],
            ],
            'total' => 2
        ], 'Список пользователей');
        
        \Architect\Support\Debug::addData('config', [
            'app_name' => 'Test App',
            'version' => '1.0.0',
            'debug' => true
        ], 'Конфигурация приложения');
        
        // 4. Счётчики
        \Architect\Support\Debug::incrementCounter('api', 'requests', 1);
        \Architect\Support\Debug::incrementCounter('api', 'requests', 1);
        \Architect\Support\Debug::incrementCounter('api', 'errors', 1);
        \Architect\Support\Debug::incrementCounter('cache', 'hits', 5);
        \Architect\Support\Debug::incrementCounter('cache', 'misses', 2);
        
        // 5. События
        \Architect\Support\Debug::markEvent('user_login', ['user_id' => 1, 'ip' => '127.0.0.1']);
        \Architect\Support\Debug::markEvent('page_view', ['page' => '/debugtest', 'ref' => '/']);
        
        // 6. Метаданные
        \Architect\Support\Debug::setMetadata('request_id', 'req_' . time());
        \Architect\Support\Debug::setMetadata('session_id', 'sess_abc123');
        
        // 7. Логирование через фасад
        \Architect\Support\Debug::log('Тестовое логирование', 'info', ['source' => 'debugtest']);
        
        // 8. Тестируем debug сервис напрямую
        $debug = $this->get('debug');
        $debug->log('Сообщение из контроллера', 'info');
        
        // SQL запросы
        $debug->query('SELECT * FROM users WHERE id = ?', 0.001, [1]);
        $debug->query('SELECT * FROM posts WHERE user_id = ?', 0.05, [1]); // медленный
        $debug->query('INSERT INTO logs (message) VALUES (?)', 0.002, ['test']);
        
        // Кеш
        $debug->cacheHit('user:1');
        $debug->cacheHit('user:2');
        $debug->cacheMiss('user:3');
        $debug->cacheSet('user:4');
        
        // Данные для вида
        $this->extArray = [
            'title' => 'Debug Test',
            'message' => 'Тестовые данные добавлены в Debug панель',
            'timers' => [
                'query_execution' => $duration ? round($duration * 1000, 2) . ' ms' : 'N/A'
            ]
        ];
    }
    
    public function index_app_output(): void {
        $this->render('index', $this->extArray);
    }
}
