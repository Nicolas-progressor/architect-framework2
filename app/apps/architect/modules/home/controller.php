<?php

declare(strict_types=1);

namespace app\architect\modules\home\controller;
use Architect\Helpers\Facades\Helper_Title;

use pattern\controller;
use Architect\Statics\Statics;

class home extends controller
{
    public function index_app_data(): void
    {
        // Установка заголовка страницы
        Helper_Title::set('Компоненты - Architect Framework');
        
    }
    
    public function index_app_output(): void
    {
        $this->render('index');
    }
}