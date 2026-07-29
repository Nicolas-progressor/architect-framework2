<?php

declare(strict_types=1);

namespace app\home\modules\services\controller;

use Architect\Helpers\Facades\Helper_Breadcrumbs;
use Architect\Helpers\Facades\Helper_Title;
use pattern\controller;

class services extends controller
{
    public function index_app_data(): void
    {
        $model = $this->getModel('services');
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
