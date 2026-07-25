<?php

declare(strict_types=1);

namespace app\blueprint\modules\contact\model;

use Architect\Services\Mvc\ModelBase;

class contact extends ModelBase
{
    public function getPageData(): array
    {
        return [
            'title' => 'Контакты - Blueprint App',
            'description' => 'Свяжитесь с нами'
        ];
    }
    
    public function getBreadcrumbs(): array
    {
        return [
            ['title' => 'Главная', 'url' => '/blueprint/'],
            ['title' => 'Контакты', 'url' => '/blueprint/contact']
        ];
    }
    
    public function getContactInfo(): array
    {
        return [
            'email' => 'hello@blueprint.dev',
            'phone' => '+7 (999) 123-45-67',
            'address' => 'г. Москва, ул. Примерная, д. 1',
            'social' => [
                ['icon' => 'bi-github', 'url' => 'https://github.com', 'name' => 'GitHub'],
                ['icon' => 'bi-twitter', 'url' => 'https://twitter.com', 'name' => 'Twitter'],
                ['icon' => 'bi-telegram', 'url' => 'https://telegram.org', 'name' => 'Telegram']
            ]
        ];
    }
}
