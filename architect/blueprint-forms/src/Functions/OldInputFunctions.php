<?php

/**
 * Old Input Functions
 * 
 * Provides old input retrieval functions for Blueprint templates.
 * 
 * @package     Architect\BlueprintForms\Functions
 * @author      Architect Team <team@architect.dev>
 * @license     MIT
 */

declare(strict_types=1);

namespace Architect\BlueprintForms\Functions;

use Blueprint\Engine\Blueprint;
use Architect\BlueprintForms\Contracts\FormFunctionProviderInterface;

/**
 * Old input functions for preserving form values.
 */
final class OldInputFunctions implements FormFunctionProviderInterface
{
    /**
     * Register old input functions with Blueprint.
     */
    public function register(Blueprint $blueprint): void
    {
        $blueprint->registerFunction('old', fn(string $key, mixed $default = ''): mixed => $this->getOld($key, $default));
    }

    /**
     * Get old input value from session or POST.
     */
    private function getOld(string $key, mixed $default): mixed
    {
        if (session_status() !== PHP_SESSION_NONE && isset($_SESSION['_old_input'][$key])) {
            return $_SESSION['_old_input'][$key];
        }

        if (isset($_POST[$key])) {
            return $_POST[$key];
        }

        return $default;
    }
}
