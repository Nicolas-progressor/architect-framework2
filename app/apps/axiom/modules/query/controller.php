<?php

declare(strict_types=1);

namespace app\axiom\modules\query\controller;

use Architect\Helpers\Facades\Helper_Request;
use Architect\Helpers\Facades\Helper_Title;
use pattern\controller;

class query extends controller
{
    public function index_app_data(): void
    {
        $model = $this->getModel('query');
        $this->ext['active'] = 'query';
        $this->ext['users'] = $model->getUsers();
        $this->ext['stats'] = $model->getStats();
    }

    public function index_app_output(): void
    {
        Helper_Title::set('Query Builder - Axiom ORM');
        $this->render('index');
    }

    public function run_app_output(): void
    {
        $model = $this->getModel('query');

        // Use Query facade to get GET parameters - ensure strings, not null
        $type = (string) Helper_Request::get('type', 'select');
        $name = (string) (Helper_Request::get('name') ?? '');
        $email = (string) (Helper_Request::get('email') ?? '');

        $result = $model->runQuery($type, $name, $email);

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    /**
     * AJAX endpoint for getting users and stats
     */
    public function data_app_output(): void
    {
        $model = $this->getModel('query');

        header('Content-Type: application/json');
        echo json_encode([
            'users' => $model->getUsers(),
            'stats' => $model->getStats(),
        ]);
    }
}
