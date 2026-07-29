<?php

declare(strict_types=1);

namespace app\modules\pages\controller;

use pattern\controller;

/**
 * Страницы (общий модуль - вне приложения)
 * Пример: можно подключить шаблон в modulebootstrap
 */
class pages extends controller
{
    public function index_app_output(): void
    {
        // По умолчанию - без шаблона (для общих модулей)
        $this->ext['title'] = 'Страница';
        $this->ext['content'] = 'Простая страница без шаблона';

        $this->render('index');
    }

    /**
     * Пример с принудительным включением шаблона
     */
    public function withtemplate_app_output(): void
    {
        // Включаем шаблон приложения
        $this->setTemplate('bootstrap');

        $this->ext['title'] = 'Страница с шаблоном';
        $this->ext['content'] = 'Эта страница использует шаблон приложения';

        $this->render('withtemplate');
    }
}
