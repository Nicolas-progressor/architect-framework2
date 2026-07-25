<?php

declare(strict_types=1);

namespace app\axiom\modules\home\controller;
use Architect\Helpers\Facades\Helper_Title;

use pattern\controller;

class home extends controller
{
    public function index_app_output(): void
    {
        Helper_Title::set('Axiom ORM - Тестирование');
        $this->ext['active'] = 'home';
        
        // Получаем данные от модели и передаём в представление
        $model = $this->getModel('home');
        $this->render('index', [
            'features' => $model->getFeatures(),
            'stats' => $model->getStats()
        ]);
    }
}
