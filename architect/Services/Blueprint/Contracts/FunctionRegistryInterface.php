<?php

declare(strict_types=1);

namespace Architect\Services\Blueprint\Contracts;

/**
 * Interface for template function registration
 */
interface FunctionRegistryInterface
{
    /**
     * Register all functions with the given registrar
     */
    public function register(callable $registrar): void;

    /**
     * Get function definitions
     * @return array<string, callable>
     */
    public function getFunctions(): array;
}
