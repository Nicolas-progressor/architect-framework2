<?php

declare(strict_types=1);

namespace Architect\Helpers\Core\Contracts;

/**
 * Interface for helper services.
 */
interface HelperInterface
{
    /**
     * Get the helper's alias (short name used for registration).
     */
    public static function getAlias(): string;

    /**
     * Get the helper's service class (fully qualified class name).
     * If null, the class implementing this interface will be used.
     */
    public static function getServiceClass(): ?string;

    /**
     * Get the helper's facade class (fully qualified class name).
     * If null, a generic facade will be used.
     */
    public static function getFacadeClass(): ?string;

    /**
     * Get the helper's methods for automatic registration in template engines.
     *
     * @return array<string, string> Map of method name to description (optional)
     */
    public static function getMethods(): array;
}
