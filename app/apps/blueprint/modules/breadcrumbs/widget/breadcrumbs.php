<?php

declare(strict_types=1);

namespace app\blueprint\modules\breadcrumbs\widget;

use Architect\Helpers\Facades\Helper_Breadcrumbs;
use pattern\controller;

class breadcrumbs extends controller
{
    public function create_app_output(): void
    {
        // Получаем крошки после того как контроллер страницы их добавил
        $this->ext['breadcrumbs'] = Helper_Breadcrumbs::all();

        $this->display('breadcrumbs');
    }
}
