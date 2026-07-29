<?php

declare(strict_types=1);

namespace app\axiom\modules\navbar\widget;

use pattern\controller;

class navbar extends controller
{
    public function create_app_data(): void
    {
        $model = $this->model->load('navbar', 'navbar');
        $this->ext['menu'] = $model->getMenu();
    }

    public function create_app_output(): void
    {
        $this->display('navbar');
    }
}
