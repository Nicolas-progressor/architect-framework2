<?php

declare(strict_types=1);

namespace app\architect\modules\components\controller;
use Architect\Helpers\Facades\Helper_Title;
use Architect\Helpers\Facades\Helper_Breadcrumbs;

use pattern\controller;
use Architect\Statics\Statics;

class components extends controller
{
    public function index_app_data(): void
    {
        // Установка заголовка страницы
        Helper_Title::set('Компоненты - Architect Framework');
        
        // Добавление хлебных крошек
        Helper_Breadcrumbs::add('Главная', '/');
        Helper_Breadcrumbs::add('Компоненты', '/components');
    }
    
    public function index_app_output(): void
    {
        $this->render('index');
    }
}