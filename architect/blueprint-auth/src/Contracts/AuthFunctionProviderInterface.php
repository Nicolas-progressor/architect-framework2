<?php

/**
 * Auth Function Provider Interface
 *
 * Defines contract for auth function registration.
 *
 * @package     Architect\BlueprintAuth\Contracts
 * @author      Architect Team <team@architect.dev>
 * @license     MIT
 */

declare(strict_types=1);

namespace Architect\BlueprintAuth\Contracts;

use Blueprint\Engine\Blueprint;

/**
 * Interface for auth function providers.
 */
interface AuthFunctionProviderInterface
{
    /**
     * Register auth functions with Blueprint.
     */
    public function register(Blueprint $blueprint): void;
}
