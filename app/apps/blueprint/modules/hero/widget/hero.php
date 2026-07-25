<?php

declare(strict_types=1);

namespace app\blueprint\modules\hero\widget;

use pattern\controller;

class hero extends controller
{
    public function create_app_data(): void
    {
        $model = $this->getModel('hero');
        $this->ext['features'] = $model->getFeatures();
    }
    
    public function create_app_output(): void
    {
        $this->display('hero');
    }
}
