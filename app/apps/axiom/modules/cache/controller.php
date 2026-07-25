<?php

declare(strict_types=1);

namespace app\axiom\modules\cache\controller;
use Architect\Helpers\Facades\Helper_Title;

use pattern\controller;


class cache extends controller
{
    public function index_app_data(): void
    {
        $model = $this->getModel('cache');
        $this->ext['active'] = 'cache';
        $this->ext['stats'] = $model->getStats();
    }
    
    public function index_app_output(): void
    {
        Helper_Title::set('Cache - Axiom ORM');
        $this->render('index');
    }
    
    public function clear_app_data(): void
    {
        $model = $this->getModel('cache');
        $result = $model->clearCache();
        $this->json($result);
    }
}
