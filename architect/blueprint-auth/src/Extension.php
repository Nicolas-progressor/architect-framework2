<?php

/**
 * Blueprint Auth Extension
 * 
 * Integrates Auth system with Blueprint templates.
 * Provides authentication and authorization functions.
 * 
 * @package     Architect\BlueprintAuth
 * @author      Architect Team <team@architect.dev>
 * @license     MIT
 */

declare(strict_types=1);

namespace Architect\BlueprintAuth;

use Blueprint\Engine\Blueprint;
use Blueprint\Engine\BlueprintExtension;
use Architect\BlueprintAuth\Contracts\AuthFunctionProviderInterface;
use Architect\BlueprintAuth\Functions\AuthCheckFunctions;
use Architect\BlueprintAuth\Functions\AuthPermissionFunctions;
use Architect\BlueprintAuth\Functions\AuthUrlFunctions;

/**
 * Blueprint extension for auth integration.
 * 
 * Usage in templates:
 *   {% if auth_check() %}
 *       Welcome, {{ auth_user().username }}!
 *   {% endif %}
 *   
 *   {% if auth_can('manage_users') %}
 *       <a href="/admin/users">Manage Users</a>
 *   {% endif %}
 */
final class Extension implements BlueprintExtension
{
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
     * @return array<AuthFunctionProviderInterface>
     */
    private function getFunctionProviders(): array
    {
        return [
            new AuthCheckFunctions(),
            new AuthPermissionFunctions(),
            new AuthUrlFunctions(),
        ];
    }
}

