<?php

declare(strict_types=1);

namespace app\home\modules\footer\model;

use Architect\Services\Mvc\ModelBase;

class footer extends ModelBase
{
    public function getYear(): int
    {
        return (int) date('Y');
    }
}
