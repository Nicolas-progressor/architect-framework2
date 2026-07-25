<?php

declare(strict_types=1);

namespace app\axiom\modules\infoentity\model;

use Architect\Services\Mvc\ModelBase;
use Axiom\Orm\Orm;
use Axiom\Entity\EntityManager;
use App\Entity\AxiomInfo;

class infoentity extends ModelBase
{
    /**
     * Get all info using Entity Manager
     */
    public function getAllInfo(): array
    {
        try {
            $rows = Orm::table('axiom_info')
                ->orderBy('sort_order', 'ASC')
                ->get() ?? [];
            
            // Map to entities
            $entities = [];
            foreach ($rows as $row) {
                $entity = EntityManager::map(AxiomInfo::class, $row);
                if ($entity === null) {
                    continue;
                }
                $entities[] = $entity;
            }
            
            return $entities;
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Get info grouped by category using Entity
     */
    public function getInfoByCategory(): array
    {
        try {
            $all = $this->getAllInfo();
            
            $byCategory = [];
            foreach ($all as $entity) {
                $category = $entity->getCategory() ?? 'other';
                if (!isset($byCategory[$category])) {
                    $byCategory[$category] = [];
                }
                $byCategory[$category][] = $entity;
            }
            
            return $byCategory;
        } catch (\Exception $e) {
            return [];
        }
    }
}
