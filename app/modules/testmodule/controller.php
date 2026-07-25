<?php

declare(strict_types=1);

namespace app\modules\testmodule\controller;

use pattern\controller;

class testmodule extends controller
{
    public function index_app_load(): void {
        $language = $this->get('language');
        $language->file('testmodule', 'modules');
    }
    
    public function index_app_data(): void {
        // Загружаем модель из глобального модуля (true = искать в app/modules/)
        $model = $this->model->load('testmodule', true);
        
        if ($model) {
            $data = $model->getData();
            $this->extArray = ['model_data' => $data];
        } else {
            $this->extArray = ['model_data' => 'Model not found'];
        }
    }
    
    public function index_app_output(): void {
        $this->render('index', $this->extArray);
    }
}
