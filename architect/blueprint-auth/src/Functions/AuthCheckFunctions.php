<?php

/**
 * Auth Check Functions
 *
 * Provides authentication check functions for Blueprint templates.
 *
 * @package     Architect\BlueprintAuth\Functions
 * @author      Architect Team <team@architect.dev>
 * @license     MIT
 */

declare(strict_types=1);

namespace Architect\BlueprintAuth\Functions;

use Architect\Auth\Helpers\Auth;
use Architect\BlueprintAuth\Contracts\AuthFunctionProviderInterface;
use Blueprint\Engine\Blueprint;

/**
 * Authentication check functions.
 */
final class AuthCheckFunctions implements AuthFunctionProviderInterface
{
    /**
     * Register auth check functions with Blueprint.
     */
    public function register(Blueprint $blueprint): void
    {
        $blueprint->registerFunction('auth_check', fn(): bool => Auth::check());
        $blueprint->registerFunction('is_auth', fn(): bool => Auth::check());
        $blueprint->registerFunction('authenticated', fn(): bool => Auth::check());
        $blueprint->registerFunction('auth_user', fn() => Auth::user());
        $blueprint->registerFunction('current_user', fn() => Auth::user());
        $blueprint->registerFunction('user', fn() => Auth::user());
        $blueprint->registerFunction('auth_id', fn() => Auth::id());
    }
}
