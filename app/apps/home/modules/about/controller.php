<?php

declare(strict_types=1);

namespace app\home\modules\about\controller;
use Architect\Helpers\Facades\Helper_Title;
use Architect\Helpers\Facades\Helper_Breadcrumbs;

use pattern\controller;


class about extends controller
{
    public function index_app_data(): void
    {
        $model = $this->getModel('about');
        $this->ext['page_data'] = $model->getPageData();
        $this->ext['breadcrumbs'] = $model->getBreadcrumbs();
        
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
