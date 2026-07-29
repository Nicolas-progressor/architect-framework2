<?php

declare(strict_types=1);

namespace app\axiom\modules\infoentity\controller;

use Architect\Helpers\Facades\Helper_Title;
use pattern\controller;

class infoentity extends controller
{
    public function index_app_data(): void
    {
        $model = $this->getModel('infoentity');
        $this->ext['active'] = 'infoentity';
        $this->ext['info_list'] = $model->getAllInfo();
        $this->ext['info_by_category'] = $model->getInfoByCategory();
    }

    public function index_app_output(): void
    {
        Helper_Title::set('Axiom Info (Entity) - Компоненты системы');
        $this->render('index');
    }

    /**
     * AJAX endpoint for getting info data via Entity
     */
    public function data_app_output(): void
    {
        $model = $this->getModel('infoentity');

        header('Content-Type: application/json');
        echo json_encode([
            'info_list' => $model->getAllInfo(),
            'info_by_category' => $model->getInfoByCategory(),
        ]);
    }
}
