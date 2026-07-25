<?php

declare(strict_types=1);

namespace app\modules\api\controller;

use pattern\controller;

/**
 * API контроллер (общий модуль - без приложения)
 * По умолчанию работает БЕЗ шаблона
 */
class api extends controller
{
    public function index_app_output(): void
    {
        $this->noTemplate();
        
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'ok',
            'message' => 'API работает',
            'endpoints' => [
                '/api/users' => 'Список пользователей',
                '/api/user/{id}' => 'Пользователь по ID',
            ]
        ]);
    }
    
    public function users_app_output(): void
    {
        $this->noTemplate();
        
        header('Content-Type: application/json');
        echo json_encode([
            'users' => [
                ['id' => 1, 'name' => 'Иван'],
                ['id' => 2, 'name' => 'Пётр'],
                ['id' => 3, 'name' => 'Анна'],
            ]
        ]);
    }
    
    public function user_app_output(): void
    {
        $this->noTemplate();
        
        $id = $this->segment(3, '0');
        
        header('Content-Type: application/json');
        echo json_encode([
            'id' => $id,
            'name' => 'Пользователь ' . $id,
            'email' => 'user' . $id . '@example.com',
        ]);
    }
}
