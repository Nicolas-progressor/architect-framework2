<?php

declare(strict_types=1);

namespace app\home\modules\breadcrumbs\model;
use Architect\Helpers\Facades\Helper_Breadcrumbs;

use Architect\Services\Mvc\ModelBase;


class breadcrumbs extends ModelBase
{
    public function getBreadcrumbs(): array
    {
        return Helper_Breadcrumbs::all();
    }
}
