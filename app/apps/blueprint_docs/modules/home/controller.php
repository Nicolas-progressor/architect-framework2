<?php

declare(strict_types=1);

namespace app\blueprint_docs\modules\home\controller;

use Architect\Helpers\Facades\Helper_Title;
use pattern\controller;

class home extends controller
{
    public function index_app_data(): void
    {
        $model = $this->getModel('home');
        $this->ext['page_data'] = $model->getPageData();
        $this->ext['features'] = $model->getFeatures();
    }

    public function index_app_output(): void
    {
        Helper_Title::set($this->ext['page_data']['title']);

        $this->render('index');
    }
}
