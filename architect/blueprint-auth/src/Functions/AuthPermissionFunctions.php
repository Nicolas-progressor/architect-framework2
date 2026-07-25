<?php

/**
 * Auth Permission Functions
 *
 * Provides permission check functions for Blueprint templates.
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
 * Permission and role check functions.
 */
final class AuthPermissionFunctions implements AuthFunctionProviderInterface
{
    /**
     * Register permission functions with Blueprint.
     */
    public function register(Blueprint $blueprint): void
    {
        $blueprint->registerFunction('auth_can', fn(string $permission): bool => Auth::can($permission));
        $blueprint->registerFunction('can', fn(string $permission): bool => Auth::can($permission));
        $blueprint->registerFunction('has_permission', fn(string $permission): bool => Auth::can($permission));
        $blueprint->registerFunction('auth_is', fn(string $role): bool => Auth::is($role));
        $blueprint->registerFunction('has_role', fn(string $role): bool => Auth::is($role));
        $blueprint->registerFunction('role', fn(string $role): bool => Auth::is($role));
        $blueprint->registerFunction('auth_is_admin', fn(): bool => Auth::isAdmin());
        $blueprint->registerFunction('is_admin', fn(): bool => Auth::isAdmin());
    }
}
