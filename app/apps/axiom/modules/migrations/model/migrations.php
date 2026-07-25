<?php

declare(strict_types=1);

namespace app\axiom\modules\migrations\model;

use Architect\Services\Mvc\ModelBase;
use Axiom\Migration\MigrationManager;

class migrations extends ModelBase
{
    private ?MigrationManager $manager = null;
    
    /**
     * Get migration manager instance (lazy initialization).
     */
    private function getManager(): MigrationManager
    {
        if ($this->manager === null) {
            $this->manager = new MigrationManager(ROOT_DIR . 'migrations/');
        }
        return $this->manager;
    }
    
    public function getStatus(): array
    {
        return $this->getManager()->status();
    }
    
    public function getPending(): array
    {
        return $this->getManager()->getPendingMigrations();
    }
    
    public function runMigrations(): array
    {
        try {
            $ran = $this->getManager()->migrate();
            return [
                'success' => true,
                'message' => 'Выполнено миграций: ' . count($ran),
                'migrations' => $ran
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage()
            ];
        }
    }
    
    public function rollbackMigration(): array
    {
        try {
            $rolledBack = $this->getManager()->rollback();
            return [
                'success' => true,
                'message' => 'Откачено миграций: ' . count($rolledBack),
                'migrations' => $rolledBack
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage()
            ];
        }
    }
}
