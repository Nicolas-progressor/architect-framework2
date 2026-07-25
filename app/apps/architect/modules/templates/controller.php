<?php

declare(strict_types=1);

namespace app\architect\modules\templates\controller;
use Architect\Helpers\Facades\Helper_Title;
use Architect\Helpers\Facades\Helper_Breadcrumbs;

use pattern\controller;
use Architect\Statics\Statics;

class templates extends controller
{
    public function index_app_data(): void
    {
        // Установка заголовка страницы
        Helper_Title::set('Шаблоны - Architect Framework');
        
        // Добавление хлебных крошек
        Helper_Breadcrumbs::add('Главная', '/');
        Helper_Breadcrumbs::add('Шаблоны', '/templates');
    }
    
    public function index_app_output(): void
    {
        $this->render('index');
    }
}