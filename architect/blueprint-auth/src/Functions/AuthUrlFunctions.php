<?php

/**
 * Auth URL Functions
 * 
 * Provides auth URL generation functions for Blueprint templates.
 * 
 * @package     Architect\BlueprintAuth\Functions
 * @author      Architect Team <team@architect.dev>
 * @license     MIT
 */

declare(strict_types=1);

namespace Architect\BlueprintAuth\Functions;

use Blueprint\Engine\Blueprint;
use Architect\BlueprintAuth\Contracts\AuthFunctionProviderInterface;
use Architect\Auth\Helpers\Auth;

/**
 * Auth URL generation functions.
 */
final class AuthUrlFunctions implements AuthFunctionProviderInterface
{
    /**
     * Register URL functions with Blueprint.
     */
    public function register(Blueprint $blueprint): void
    {
        $blueprint->registerFunction('login_url', fn(): string => Auth::loginUrl());
        $blueprint->registerFunction('logout_url', fn(): string => Auth::logoutUrl());
        $blueprint->registerFunction('register_url', fn(): string => Auth::registerUrl());
        $blueprint->registerFunction('password_reset_url', fn(): string => $this->getPasswordResetUrl());
        $blueprint->registerFunction('email_verification_url', fn(): string => $this->getEmailVerificationUrl());
        $blueprint->registerFunction('login_link', fn(?string $redirect = null): string => Auth::loginLink($redirect));
        $blueprint->registerFunction('logout_link', fn(?string $redirect = null): string => Auth::logoutLink($redirect));
        $blueprint->registerFunction('register_link', fn(?string $redirect = null): string => $this->getRegisterLink($redirect));
    }

    private function getPasswordResetUrl(): string
    {
        return Auth::getManager()->getPasswordResetUrl();
    }

    private function getEmailVerificationUrl(): string
    {
        return Auth::getManager()->getEmailVerificationUrl();
    }

    private function getRegisterLink(?string $redirect): string
    {
        $url = Auth::getManager()->getRegisterUrl();
        
        if ($redirect) {
            $url .= '?redirect=' . urlencode($redirect);
        }

        return $url;
    }
}
