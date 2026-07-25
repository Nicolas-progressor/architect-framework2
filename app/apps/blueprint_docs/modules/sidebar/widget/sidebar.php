<?php

declare(strict_types=1);

namespace app\blueprint_docs\modules\sidebar\widget;

use pattern\controller;

class sidebar extends controller
{
    public function create_app_data(): void
    {
        $model = $this->getModel('sidebar');
        $this->ext['sections'] = $model->getSections();
    }
    
    public function create_app_output(): void
    {
        $this->display('sidebar');
    }
}
