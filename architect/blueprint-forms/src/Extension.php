<?php

/**
 * Blueprint Forms Extension
 * 
 * Integrates Form system with Blueprint templates.
 * Provides form building and validation functions.
 * 
 * @package     Architect\BlueprintForms
 * @author      Architect Team <team@architect.dev>
 * @license     MIT
 */

declare(strict_types=1);

namespace Architect\BlueprintForms;

use Blueprint\Engine\Blueprint;
use Blueprint\Engine\BlueprintExtension;
use Architect\Services\Form\FormBuilder;
use Architect\Services\Form\CSRFTokenManager;
use Architect\BlueprintForms\Contracts\FormFunctionProviderInterface;
use Architect\BlueprintForms\Functions\CSRFFunctions;
use Architect\BlueprintForms\Functions\FormFieldFunctions;
use Architect\BlueprintForms\Functions\OldInputFunctions;

/**
 * Blueprint extension for form integration.
 * 
 * Usage in templates:
 *   {{ form_open('/submit') }}
 *   {{ text('username', old('username')) }}
 *   {{ csrf_field() }}
 *   {{ submit('Save') }}
 *   {{ form_close() }}
 */
final class Extension implements BlueprintExtension
{
    private ?FormBuilder $builder = null;
    private ?CSRFTokenManager $csrf = null;

    /**
     * Register extension with Blueprint.
     */
    public function register(Blueprint $blueprint): void
    {
        foreach ($this->getFunctionProviders() as $provider) {
            $provider->register($blueprint);
        }
    }

    /**
     * Get function providers.
     * 
     * @return array<FormFunctionProviderInterface>
     */
    private function getFunctionProviders(): array
    {
        return [
            new CSRFFunctions($this->getCSRF()),
            new FormFieldFunctions($this->getBuilder()),
            new OldInputFunctions(),
        ];
    }

    /**
     * Get FormBuilder instance.
     */
    private function getBuilder(): FormBuilder
    {
        if ($this->builder === null) {
            $this->builder = new FormBuilder($this->getCSRF());
            $this->applyFlashData($this->builder);
        }
        
        return $this->builder;
    }

    /**
     * Get CSRF Token Manager instance.
     */
    private function getCSRF(): CSRFTokenManager
    {
        if ($this->csrf === null) {
            $this->csrf = new CSRFTokenManager();
        }

        return $this->csrf;
    }

    /**
     * Apply flash data from session to builder.
     */
    private function applyFlashData(FormBuilder $builder): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            return;
        }
        
        if (isset($_SESSION['_old_input'])) {
            $builder->setData($_SESSION['_old_input']);
            unset($_SESSION['_old_input']);
        }

        if (isset($_SESSION['_form_errors'])) {
            $builder->setErrors($_SESSION['_form_errors']);
            unset($_SESSION['_form_errors']);
        }
    }
}

