<?php

declare(strict_types=1);

namespace app\architect\modules\architecture\controller;

use Architect\Helpers\Facades\Helper_Breadcrumbs;
use Architect\Helpers\Facades\Helper_Title;
use pattern\controller;

class architecture extends controller
{
    public function index_app_data(): void
    {
        // Установка заголовка страницы
        Helper_Title::set('Архитектура - Architect Framework');

        // Добавление хлебных крошек
        Helper_Breadcrumbs::add('Главная', '/');
        Helper_Breadcrumbs::add('Архитектура', '/architecture');
    }

    public function index_app_output(): void
    {
        $this->render('index');
    }
}
