<?php

declare(strict_types=1);

namespace app\home\modules\card\widget;

use pattern\controller;

class card extends controller
{
    public function create_app_data(): void
    {
        $model = $this->getModel('card');
        $this->ext['cards'] = $model->getCards();
    }

    public function create_app_output(): void
    {
        $this->display('card');
    }
}
