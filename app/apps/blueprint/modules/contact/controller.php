<?php

declare(strict_types=1);

namespace app\blueprint\modules\contact\controller;

use Architect\Helpers\Facades\Helper_Breadcrumbs;
use Architect\Helpers\Facades\Helper_Request;
use Architect\Helpers\Facades\Helper_Title;
use pattern\controller;

class contact extends controller
{
    public function index_app_data(): void
    {
        $model = $this->getModel('contact');
        $this->ext['page_data'] = $model->getPageData();
        $this->ext['breadcrumbs'] = $model->getBreadcrumbs();
        $this->ext['contact_info'] = $model->getContactInfo();

        foreach ($this->ext['breadcrumbs'] as $crumb) {
            Helper_Breadcrumbs::add($crumb['title'], $crumb['url']);
        }
    }

    public function index_app_output(): void
    {
        Helper_Title::set($this->ext['page_data']['title']);

        $this->render('index');
    }

    public function send_app_data(): void
    {
        // Обработка отправки формы
        $name = Helper_Request::post('name');
        $email = Helper_Request::post('email');
        $message = Helper_Request::post('message');

        if ($name && $email && $message) {
            // Здесь можно добавить логику отправки
            $this->ext['success'] = true;
            $this->ext['message'] = 'Сообщение успешно отправлено!';
        } else {
            $this->ext['success'] = false;
            $this->ext['message'] = 'Пожалуйста, заполните все поля формы.';
        }
    }

    public function send_app_output(): void
    {
        // Возвращаем JSON ответ
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $this->ext['success'] ?? false,
            'message' => $this->ext['message'] ?? '',
        ]);
    }
}
