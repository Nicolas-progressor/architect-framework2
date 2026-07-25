<?php

/**
 * Form Function Provider Interface
 * 
 * Defines contract for form function registration.
 * 
 * @package     Architect\BlueprintForms\Contracts
 * @author      Architect Team <team@architect.dev>
 * @license     MIT
 */

declare(strict_types=1);

namespace Architect\BlueprintForms\Contracts;

use Blueprint\Engine\Blueprint;

/**
 * Interface for form function providers.
 */
interface FormFunctionProviderInterface
{
    /**
     * Register form functions with Blueprint.
     */
    public function register(Blueprint $blueprint): void;
}
