<?php

declare(strict_types=1);

namespace app\blueprint\modules\home\controller;
use Architect\Helpers\Facades\Helper_Title;
use Architect\Helpers\Facades\Helper_Breadcrumbs;

use pattern\controller;


class home extends controller
{
    public function index_app_data(): void
    {
        $model = $this->getModel('home');
        $this->ext['page_data'] = $model->getPageData();
        $this->ext['breadcrumbs'] = $model->getBreadcrumbs();
        $this->ext['show_hero'] = true;
        
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
