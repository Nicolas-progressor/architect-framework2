<?php

declare(strict_types=1);

namespace app\axiom\modules\query\model;

use Architect\Services\Mvc\ModelBase;
use Axiom\Orm\Orm;

class query extends ModelBase
{
    private bool $seeded = false;
    
    public function getUsers(): array
    {
        // Проверяем и создаём тестовые данные если нужно
        $this->ensureTestData();
        
        try {
            return Orm::table('axiom_users')
                ->orderBy('id', 'DESC')
                ->limit(10)
                ->get() ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Проверить наличие данных и создать тестовые если таблица пуста
     */
    private function ensureTestData(): void
    {
        if ($this->seeded) {
            return;
        }
        
        try {
            $count = Orm::table('axiom_users')->count() ?? 0;
            
            if ($count === 0) {
                $this->seedTestData();
            }
            
            $this->seeded = true;
        } catch (\Throwable $e) {
            // Таблица не существует - ничего не делаем
            // Для отладки можно записать в лог
            error_log("Axiom Query: " . $e->getMessage());
            $this->seeded = true;
        }
    }
    
    /**
     * Создать тестовые данные
     */
    private function seedTestData(): void
    {
        $testUsers = [
            ['name' => 'Алексей Иванов', 'email' => 'alexey@example.com', 'status' => 'active'],
            ['name' => 'Мария Петрова', 'email' => 'maria@example.com', 'status' => 'active'],
            ['name' => 'Иван Сидоров', 'email' => 'ivan@example.com', 'status' => 'active'],
            ['name' => 'Елена Смирнова', 'email' => 'elena@example.com', 'status' => 'inactive'],
            ['name' => 'Дмитрий Козлов', 'email' => 'dmitry@example.com', 'status' => 'active'],
            ['name' => 'Анна Морозова', 'email' => 'anna@example.com', 'status' => 'active'],
            ['name' => 'Сергей Волков', 'email' => 'sergey@example.com', 'status' => 'inactive'],
            ['name' => 'Ольга Новикова', 'email' => 'olga@example.com', 'status' => 'active'],
            ['name' => 'Павел Захаров', 'email' => 'pavel@example.com', 'status' => 'active'],
            ['name' => 'Наталья Кузнецова', 'email' => 'natalia@example.com', 'status' => 'active'],
        ];
        
        foreach ($testUsers as $user) {
            try {
                Orm::table('axiom_users')
                    ->insert()
                    ->set([
                        'name' => $user['name'],
                        'email' => $user['email'],
                        'status' => $user['status'],
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ])
                    ->execute();
            } catch (\Throwable $e) {
                // Пропускаем ошибки - возможно таблица не существует
                error_log("Axiom Query seed error: " . $e->getMessage());
            }
        }
    }
    
    public function getStats(): array
    {
        try {
            return [
                'total' => Orm::table('axiom_users')->count() ?? 0,
                'active' => Orm::table('axiom_users')->where('status', '=', 'active')->count() ?? 0,
                'inactive' => Orm::table('axiom_users')->where('status', '=', 'inactive')->count() ?? 0
            ];
        } catch (\Exception $e) {
            return ['total' => 0, 'active' => 0, 'inactive' => 0];
        }
    }
    
    public function runQuery(string $type, string $name = '', string $email = ''): array
    {
        try {
            switch ($type) {
                case 'insert':
                    $id = Orm::table('axiom_users')
                        ->insert()
                        ->set([
                            'name' => $name,
                            'email' => $email,
                            'status' => 'active',
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ])
                        ->execute();
                    return ['success' => true, 'message' => "Пользователь создан с ID: $id", 'id' => $id];
                
                case 'select':
                    $users = Orm::table('axiom_users')
                        ->where('name', 'LIKE', "%{$name}%")
                        ->orderBy('id', 'DESC')
                        ->limit(10)
                        ->get();
                    return ['success' => true, 'users' => $users ?? []];
                
                case 'update':
                    // Используем email или name для поиска
                    $builder = Orm::table('axiom_users');
                    
                    if (!empty($email)) {
                        $builder->where('email', '=', $email);
                    } elseif (!empty($name)) {
                        $builder->where('name', 'LIKE', "%{$name}%");
                    } else {
                        return ['success' => false, 'message' => 'Укажите email или имя для обновления'];
                    }
                    
                    $affected = $builder->update('axiom_users')
                        ->set(['status' => 'inactive'])
                        ->execute();
                    
                    return ['success' => true, 'message' => "Обновлено записей: $affected"];
                
                case 'delete':
                    $deleted = Orm::table('axiom_users')
                        ->delete()
                        ->where('status', '=', 'inactive')
                        ->execute();
                    return ['success' => true, 'message' => "Удалено записей: $deleted"];
                
                default:
                    return ['success' => false, 'message' => 'Неизвестный тип операции'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()];
        }
    }
}
