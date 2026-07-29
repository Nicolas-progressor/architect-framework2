<?php

declare(strict_types=1);

namespace app\modules\breadcrumbs\widget;

use pattern\controller;

class Breadcrumbs extends controller
{
    public function __construct($container)
    {
        parent::__construct($container, 'breadcrumbs', true);
    }

    public function create_app_data(): void
    {
        // Подготовка данных
    }

    public function create_app_output(): void
    {
        $this->display('breadcrumbs');
    }
}
