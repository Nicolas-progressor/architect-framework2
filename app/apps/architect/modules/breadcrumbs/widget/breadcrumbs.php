<?php

declare(strict_types=1);

namespace app\architect\modules\breadcrumbs\widget;

use pattern\controller;
use Architect\Helpers\Facades\Helper_Breadcrumbs;

class breadcrumbs extends controller
{
    public function create_app_data(): void
    {
        // Данные получаем в _app_output, после добавления крошек контроллером страницы
    }
    
    public function create_app_output(): void
    {
        $this->ext['breadcrumbs'] = Helper_Breadcrumbs::all();
        
        $this->display('breadcrumbs');
    }
}