<?php

declare(strict_types=1);

namespace app\admin\modules\dashboard\controller;

use pattern\controller;

class dashboard extends controller
{
    public function index_app_data(): void
    {
        $model = $this->getModel('dashboard');
        $this->ext['stats'] = $model->getStats();
    }

    public function index_app_output(): void
    {
        $this->render('index');
    }
}
