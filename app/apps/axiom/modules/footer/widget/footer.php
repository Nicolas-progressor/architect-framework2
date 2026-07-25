<?php

declare(strict_types=1);

namespace app\axiom\modules\footer\widget;

use pattern\controller;

class footer extends controller
{
    public function index_app_output(): void
    {
        // display() сразу выводит контент
        $this->display('footer');
    }
}
