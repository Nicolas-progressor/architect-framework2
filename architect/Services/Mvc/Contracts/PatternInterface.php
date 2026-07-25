<?php

declare(strict_types=1);

namespace Architect\Services\Mvc\Contracts;

/**
 * Interface for Pattern service.
 *
 * Defines the contract for MVC pattern execution.
 *
 * @package Architect\Services\Mvc\Contracts
 */
interface PatternInterface
{
    /**
     * Run MVC pattern.
     *
     * Executes the MVC lifecycle through statement hooks.
     */
    public function run(): void;
}
