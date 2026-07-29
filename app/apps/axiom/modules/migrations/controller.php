<?php

declare(strict_types=1);

namespace app\axiom\modules\migrations\controller;

use Architect\Helpers\Facades\Helper_Title;
use pattern\controller;

class migrations extends controller
{
    public function index_app_data(): void
    {
        $model = $this->getModel('migrations');
        $this->ext['active'] = 'migrations';
        $this->ext['status'] = $model->getStatus();
        $this->ext['pending'] = $model->getPending();
    }

    public function index_app_output(): void
    {
        Helper_Title::set('Миграции - Axiom ORM');
        $this->render('index');
    }

    public function run_app_data(): void
    {
        $model = $this->getModel('migrations');
        $result = $model->runMigrations();
        $this->json($result);
    }

    public function rollback_app_data(): void
    {
        $model = $this->getModel('migrations');
        $result = $model->rollbackMigration();
        $this->json($result);
    }
}
