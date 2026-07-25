<?php

declare(strict_types=1);

namespace app\home\modules\test\controller;

use pattern\controller;

class TestController extends controller
{
    public function index_app_output(): void
    {
        $this->render('index');
    }}
