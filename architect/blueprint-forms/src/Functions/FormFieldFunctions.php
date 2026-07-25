<?php

/**
 * Form Field Functions
 * 
 * Provides form field generation functions for Blueprint templates.
 * 
 * @package     Architect\BlueprintForms\Functions
 * @author      Architect Team <team@architect.dev>
 * @license     MIT
 */

declare(strict_types=1);

namespace Architect\BlueprintForms\Functions;

use Architect\Services\Form\FormBuilder;
use Blueprint\Engine\Blueprint;
use Architect\BlueprintForms\Contracts\FormFunctionProviderInterface;

/**
 * Form field functions for templates.
 */
final class FormFieldFunctions implements FormFunctionProviderInterface
{
    private FormBuilder $builder;

    public function __construct(FormBuilder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * Register form field functions with Blueprint.
     */
    public function register(Blueprint $blueprint): void
    {
        $this->registerFormTags($blueprint);
        $this->registerInputFields($blueprint);
        $this->registerComplexFields($blueprint);
        $this->registerButtons($blueprint);
        $this->registerErrorFunctions($blueprint);
    }

    /**
     * Register form open/close functions.
     */
    private function registerFormTags(Blueprint $blueprint): void
    {
        $blueprint->registerFunction('form_open', fn(string $action, string $method = 'POST', array $attrs = []): string => $this->builder->open($action, $method, $attrs));
        $blueprint->registerFunction('form_close', fn(): string => $this->builder->close());
        $blueprint->registerFunction('form_end', fn(): string => $this->builder->close());
    }

    /**
     * Register input field functions.
     */
    private function registerInputFields(Blueprint $blueprint): void
    {
        $fields = [
            'text' => 'textField',
            'email' => 'emailField',
            'password' => 'passwordField',
            'hidden' => 'hidden',
            'number' => 'numberField',
            'search' => 'searchField',
            'tel' => 'telField',
            'url' => 'urlField',
            'date' => 'dateField',
            'time' => 'timeField',
            'datetime' => 'datetimeField',
            'color' => 'colorField',
            'range' => 'rangeField',
            'file' => 'fileField',
        ];

        foreach ($fields as $alias => $method) {
            $blueprint->registerFunction("{$alias}_field", [$this->builder, $method]);
            $blueprint->registerFunction($alias, [$this->builder, $method]);
        }
    }

    /**
     * Register complex field functions.
     */
    private function registerComplexFields(Blueprint $blueprint): void
    {
        $blueprint->registerFunction('textarea', [$this->builder, 'textarea']);
        $blueprint->registerFunction('textarea_field', [$this->builder, 'textarea']);
        $blueprint->registerFunction('select', [$this->builder, 'select']);
        $blueprint->registerFunction('select_field', [$this->builder, 'select']);
        $blueprint->registerFunction('checkbox', [$this->builder, 'checkbox']);
        $blueprint->registerFunction('radio', [$this->builder, 'radio']);
        $blueprint->registerFunction('radio_field', [$this->builder, 'radio']);
    }

    /**
     * Register button functions.
     */
    private function registerButtons(Blueprint $blueprint): void
    {
        $blueprint->registerFunction('submit_button', [$this->builder, 'submitButton']);
        $blueprint->registerFunction('submit', [$this->builder, 'submitButton']);
        $blueprint->registerFunction('reset_button', [$this->builder, 'resetButton']);
        $blueprint->registerFunction('reset', [$this->builder, 'resetButton']);
        $blueprint->registerFunction('button', [$this->builder, 'button']);
    }

    /**
     * Register error display functions.
     */
    private function registerErrorFunctions(Blueprint $blueprint): void
    {
        $blueprint->registerFunction('error', [$this->builder, 'renderError']);
        $blueprint->registerFunction('error_for', [$this->builder, 'renderError']);
        $blueprint->registerFunction('errors', [$this->builder, 'renderAllErrors']);
        $blueprint->registerFunction('has_error', [$this->builder, 'hasError']);
    }
}
