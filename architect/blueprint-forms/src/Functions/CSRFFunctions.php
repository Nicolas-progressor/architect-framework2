<?php

/**
 * CSRF Functions
 * 
 * Provides CSRF token functions for Blueprint templates.
 * 
 * @package     Architect\BlueprintForms\Functions
 * @author      Architect Team <team@architect.dev>
 * @license     MIT
 */

declare(strict_types=1);

namespace Architect\BlueprintForms\Functions;

use Architect\Services\Form\CSRFTokenManager;
use Blueprint\Engine\Blueprint;
use Architect\BlueprintForms\Contracts\FormFunctionProviderInterface;

/**
 * CSRF token functions for templates.
 */
final class CSRFFunctions implements FormFunctionProviderInterface
{
    private CSRFTokenManager $csrf;

    public function __construct(CSRFTokenManager $csrf)
    {
        $this->csrf = $csrf;
    }

    /**
     * Register CSRF functions with Blueprint.
     */
    public function register(Blueprint $blueprint): void
    {
        $blueprint->registerFunction('csrf_token', fn(string $form = 'default'): string => $this->getToken($form));
        $blueprint->registerFunction('csrf_field', fn(string $form = 'default'): string => $this->getField($form));
    }

    private function getToken(string $form): string
    {
        return $this->csrf->generateToken($form);
    }

    private function getField(string $form): string
    {
        return $this->csrf->getTokenField($form);
    }
}
