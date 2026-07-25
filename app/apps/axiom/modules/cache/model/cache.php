<?php

declare(strict_types=1);

namespace app\axiom\modules\cache\model;

use Architect\Services\Mvc\ModelBase;

class cache extends ModelBase
{
    public function getStats(): array
    {
        return [
            'driver' => 'array (demo)',
            'hits' => rand(10, 100),
            'misses' => rand(1, 20),
            'size' => rand(100, 5000) . ' KB'
        ];
    }
    
    public function clearCache(): array
    {
        try {
            // В демо режиме просто возвращаем успех
            return [
                'success' => true,
                'message' => 'Кэш очищен'
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage()
            ];
        }
    }
}
