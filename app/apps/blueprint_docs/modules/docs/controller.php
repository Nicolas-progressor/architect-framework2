<?php

declare(strict_types=1);

namespace app\blueprint_docs\modules\docs\controller;

use Architect\Helpers\Facades\Helper_Title;
use pattern\controller;

class docs extends controller
{
    protected array $pages = [
        'installation' => [
            'title' => 'Установка — Blueprint',
            'prev' => null,
            'next' => 'syntax',
        ],
        'syntax' => [
            'title' => 'Синтаксис — Blueprint',
            'prev' => 'installation',
            'next' => 'variables',
        ],
        'variables' => [
            'title' => 'Переменные — Blueprint',
            'prev' => 'syntax',
            'next' => 'filters',
        ],
        'filters' => [
            'title' => 'Фильтры — Blueprint',
            'prev' => 'variables',
            'next' => 'functions',
        ],
        'functions' => [
            'title' => 'Функции — Blueprint',
            'prev' => 'filters',
            'next' => 'controlStructures',
        ],
        'controlStructures' => [
            'title' => 'Управляющие конструкции — Blueprint',
            'prev' => 'functions',
            'next' => 'inheritance',
        ],
        'inheritance' => [
            'title' => 'Наследование шаблонов — Blueprint',
            'prev' => 'controlStructures',
            'next' => 'elements',
        ],
        'elements' => [
            'title' => 'Элементы и виджеты — Blueprint',
            'prev' => 'inheritance',
            'next' => 'extending',
        ],
        'extending' => [
            'title' => 'Расширение Blueprint — Blueprint',
            'prev' => 'elements',
            'next' => 'api',
        ],
        'api' => [
            'title' => 'API Reference — Blueprint',
            'prev' => 'extending',
            'next' => 'integrations',
        ],
        'integrations' => [
            'title' => 'Интеграции — Blueprint',
            'prev' => 'api',
            'next' => null,
        ],
    ];

    protected function setPageData(string $action): void
    {
        $page = $this->pages[$action] ?? ['title' => 'Blueprint', 'prev' => null, 'next' => null];

        $this->ext['page_title'] = $page['title'];
        $this->ext['prev_page'] = $page['prev'];
        $this->ext['next_page'] = $page['next'];

        Helper_Title::set($page['title']);
    }

    protected function renderDocs(string $view): void
    {
        $this->setPageData($view);
        $this->render($view);
    }

    public function installation_app_output(): void
    {
        $this->renderDocs('installation');
    }

    public function syntax_app_output(): void
    {
        $this->renderDocs('syntax');
    }

    public function variables_app_output(): void
    {
        $this->renderDocs('variables');
    }

    public function filters_app_output(): void
    {
        $this->renderDocs('filters');
    }

    public function functions_app_output(): void
    {
        $this->renderDocs('functions');
    }

    public function controlStructures_app_output(): void
    {
        $this->renderDocs('control_structures');
    }

    public function inheritance_app_output(): void
    {
        $this->renderDocs('inheritance');
    }

    public function elements_app_output(): void
    {
        $this->renderDocs('elements');
    }

    public function extending_app_output(): void
    {
        $this->renderDocs('extending');
    }

    public function api_app_output(): void
    {
        $this->renderDocs('api');
    }

    public function integrations_app_output(): void
    {
        $this->renderDocs('integrations');
    }
}
