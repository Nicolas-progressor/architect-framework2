<?php

declare(strict_types=1);

namespace app\blueprint\modules\features\controller;
use Architect\Helpers\Facades\Helper_Title;
use Architect\Helpers\Facades\Helper_Breadcrumbs;

use pattern\controller;


class features extends controller
{
    public function index_app_data(): void
    {
        $model = $this->getModel('features');
        $this->ext['page_data'] = $model->getPageData();
        $this->ext['breadcrumbs'] = $model->getBreadcrumbs();
        $this->ext['features'] = $model->getFeatures();
        
        foreach ($this->ext['breadcrumbs'] as $crumb) {
            Helper_Breadcrumbs::add($crumb['title'], $crumb['url']);
        }
    }
    
    public function index_app_output(): void
    {
        Helper_Title::set($this->ext['page_data']['title']);
        
        $this->render('index');
    }
}
