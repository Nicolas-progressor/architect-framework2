<?php

declare(strict_types=1);

namespace app\blueprint_docs\modules\footer\widget;

use pattern\controller;

class footer extends controller
{
    public function index_app_data(): void
    {
        $this->ext['year'] = date('Y');
    }
    
    public function index_app_output(): void
    {
        $this->display('footer');
    }
}
