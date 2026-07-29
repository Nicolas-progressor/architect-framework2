<?php

declare(strict_types=1);

namespace app\axiom\modules\info\model;

use Architect\Services\Mvc\ModelBase;
use Axiom\Orm\Orm;

class info extends ModelBase
{
    /**
     * Get all info using standard Query Builder
     */
    public function getAllInfo(): array
    {
        try {
            return Orm::table('axiom_info')
                ->orderBy('sort_order', 'ASC')
                ->get() ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get info grouped by category using Query Builder
     */
    public function getInfoByCategory(): array
    {
        try {
            $all = Orm::table('axiom_info')
                ->orderBy('sort_order', 'ASC')
                ->get() ?? [];

            $byCategory = [];
            foreach ($all as $item) {
                $category = $item['category'] ?? 'other';
                if (!isset($byCategory[$category])) {
                    $byCategory[$category] = [];
                }
                $byCategory[$category][] = $item;
            }

            return $byCategory;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get info by category using Query Builder
     */
    public function getByCategory(string $category): array
    {
        try {
            return Orm::table('axiom_info')
                ->where('category', '=', $category)
                ->orderBy('sort_order', 'ASC')
                ->get() ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }
}
