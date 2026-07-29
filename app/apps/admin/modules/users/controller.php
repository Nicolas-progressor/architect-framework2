<?php

declare(strict_types=1);

namespace app\admin\modules\users\controller;

use pattern\controller;

class users extends controller
{
    public function index_app_data(): void
    {
        $model = $this->getModel('users');
        $this->ext['users_list'] = $model->getAll();
    }

    public function index_app_output(): void
    {
        $this->render('index');
    }

    public function create_app_data(): void
    {
        if ($this->getRequestMethod() === 'POST') {
            $model = $this->getModel('users');
            $data = [
                'name' => $_POST['name'] ?? '',
                'email' => $_POST['email'] ?? '',
                'role' => $_POST['role'] ?? 'user',
            ];
            $model->create($data);
            $this->redirect('/users');
        }
    }

    public function create_app_output(): void
    {
        $this->ext['user'] = ['name' => '', 'email' => '', 'role' => 'user'];
        $this->ext['is_edit'] = false;
        $this->render('form');
    }

    public function edit_app_data(): void
    {
        $id = (int) $this->segment(3, '0');
        $model = $this->getModel('users');

        if ($this->getRequestMethod() === 'POST') {
            $data = [
                'name' => $_POST['name'] ?? '',
                'email' => $_POST['email'] ?? '',
                'role' => $_POST['role'] ?? 'user',
            ];
            $model->update($id, $data);
            $this->redirect('/users');
        }

        $this->ext['user'] = $model->getById($id);
        $this->ext['is_edit'] = true;
    }

    public function edit_app_output(): void
    {
        $this->render('form');
    }

    public function delete_app_data(): void
    {
        $id = (int) $this->segment(3, '0');
        $model = $this->getModel('users');
        $model->delete($id);
        $this->redirect('/users');
    }

    private function getRequestMethod(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    private function redirect(string $path): never
    {
        header('Location: ' . $path);
        exit;
    }
}
