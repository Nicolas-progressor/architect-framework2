<?php

declare(strict_types=1);

namespace app\home\modules\footer\widget;

use pattern\controller;

class footer extends controller
{
    public function index_app_data(): void
    {
        $model = $this->getModel('footer');
        $this->ext['year'] = $model->getYear();
    }

    public function index_app_output(): void
    {
        $this->display('footer');
    }
}
