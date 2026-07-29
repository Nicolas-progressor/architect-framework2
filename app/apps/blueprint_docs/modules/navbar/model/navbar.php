<?php

declare(strict_types=1);

namespace app\blueprint_docs\modules\navbar\model;

use Architect\Helpers\Facades\Helper_Html;
use Architect\Services\Mvc\ModelBase;

class navbar extends ModelBase
{
    public function getMenu(): array
    {
        return [
            ['name' => 'Документация', 'url' => Helper_Html::href(''), 'icon' => 'book'],
            ['name' => 'GitHub', 'url' => 'https://github.com', 'icon' => 'github', 'external' => true],
        ];
    }
}
