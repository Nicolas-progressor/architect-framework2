<?php

declare(strict_types=1);

namespace app\axiom\modules\info\controller;
use Architect\Helpers\Facades\Helper_Title;

use pattern\controller;


class info extends controller
{
    public function index_app_data(): void
    {
        $model = $this->getModel('info');
        $this->ext['active'] = 'info';
        $this->ext['info_list'] = $model->getAllInfo();
        $this->ext['info_by_category'] = $model->getInfoByCategory();
    }
    
    public function index_app_output(): void
    {
        Helper_Title::set('Axiom Info - Компоненты системы');
        $this->render('index');
    }
    
    public function data_app_data(): void
    {
        $model = $this->getModel('info');
        $this->json([
            'info_list' => $model->getAllInfo(),
            'info_by_category' => $model->getInfoByCategory()
        ]);
    }
}
