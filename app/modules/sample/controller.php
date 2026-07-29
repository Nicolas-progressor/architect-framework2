<?php

namespace app\modules\sample\controller;

use pattern\controller;

class sample extends controller
{
    public function index_app_output(): void
    {
        $this->render('index');
    }

    public function home_app_output(): void
    {
        // Используем шаблон bootstrap из приложения home
        $template = $this->get('template');
        $template->setTemplateFromApp('home', 'bootstrap');

        $this->title = 'Тест - Шаблон из home';

        $this->render('home');
    }

    public function global_app_output(): void
    {
        // Используем общий шаблон из app/template/
        $template = $this->get('template');
        $template->setTemplate('bootstrap'); // Загрузит из app/template/bootstrap/

        $this->title = 'Тест - Общий шаблон';

        $this->render('global');
    }
}
