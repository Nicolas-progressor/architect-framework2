<?php

declare(strict_types=1);

namespace app\blueprint_docs\modules\navbar\widget;

use pattern\controller;

class navbar extends controller
{
    public function create_app_data(): void
    {
        $model = $this->getModel('navbar');
        $this->ext['menu'] = $model->getMenu();
        $this->ext['current_route'] = $this->get('router')->getAction();
    }
    
    public function create_app_output(): void
    {
        $this->display('navbar');
    }
}
