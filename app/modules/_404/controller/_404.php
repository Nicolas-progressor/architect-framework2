<?php

declare(strict_types=1);

namespace app\modules\_404\controller;

use Architect\Services\Mvc\Controller;

class _404 extends Controller
{
    public function index_app_data(): void
    {
        $this->ext = [
            'message' => 'Страница не найдена',
        ];
    }

    public function index_app_output(): void
    {
        $this->display('_404');
    }
}
