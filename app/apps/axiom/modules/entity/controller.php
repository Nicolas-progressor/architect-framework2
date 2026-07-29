<?php

declare(strict_types=1);

namespace app\axiom\modules\entity\controller;

use Architect\Helpers\Facades\Helper_Request;
use Architect\Helpers\Facades\Helper_Title;
use pattern\controller;

class entity extends controller
{
    public function index_app_data(): void
    {
        $model = $this->getModel('entity');
        $this->ext['active'] = 'entity';
        $this->ext['users'] = $model->getUsers();
    }

    public function index_app_output(): void
    {
        Helper_Title::set('Entity - Axiom ORM');
        $this->render('index');
    }

    public function create_app_data(): void
    {
        $model = $this->getModel('entity');

        $name = (string) (Helper_Request::get('name') ?? '');
        $email = (string) (Helper_Request::get('email') ?? '');
        $status = (string) (Helper_Request::get('status') ?? 'active');

        $result = $model->createUser($name, $email, $status);
        $this->json($result);
    }

    public function data_app_data(): void
    {
        $model = $this->getModel('entity');
        $this->json(['users' => $model->getUsers()]);
    }
}
