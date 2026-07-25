<?php

declare(strict_types=1);

namespace app\axiom\modules\entity\model;

use Architect\Services\Mvc\ModelBase;
use Axiom\Orm\Orm;

class entity extends ModelBase
{
    public function getUsers(): array
    {
        try {
            return Orm::table('axiom_users')
                ->select(['id', 'name', 'email', 'status', 'created_at'])
                ->orderBy('id', 'DESC')
                ->limit(20)
                ->get() ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }
    
    public function createUser(string $name, string $email, string $status): array
    {
        try {
            if (empty($name) || empty($email)) {
                return ['success' => false, 'message' => 'Имя и email обязательны'];
            }
            
            $id = Orm::table('axiom_users')
                ->insert()
                ->set([
                    'name' => $name,
                    'email' => $email,
                    'status' => $status,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ])
                ->execute();
            
            return [
                'success' => true,
                'message' => 'Пользователь создан',
                'id' => $id
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage()
            ];
        }
    }
}
