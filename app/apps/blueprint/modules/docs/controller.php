<?php

declare(strict_types=1);

namespace app\blueprint\modules\docs\controller;
use Architect\Helpers\Facades\Helper_Title;
use Architect\Helpers\Facades\Helper_Breadcrumbs;

use pattern\controller;


class docs extends controller
{
    public function index_app_data(): void
    {
        $model = $this->getModel('docs');
        $this->ext['page_data'] = $model->getPageData();
        $this->ext['breadcrumbs'] = $model->getBreadcrumbs();
        $this->ext['docs_sections'] = $model->getDocsSections();
        
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
