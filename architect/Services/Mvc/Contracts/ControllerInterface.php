<?php

declare(strict_types=1);

namespace Architect\Services\Mvc\Contracts;

/**
 * Interface for MVC controllers.
 *
 * Defines the contract for controller classes including
 * module/action access and lifecycle stage execution.
 *
 * @package Architect\Services\Mvc\Contracts
 */
interface ControllerInterface
{
    /**
     * Get current module name.
     *
     * @return string
     */
    public function getModule(): string;

    /**
     * Get current action name.
     *
     * @return string
     */
    public function getAction(): string;

    /**
     * Execute lifecycle stage.
     *
     * Calls the appropriate method on the controller for the given stage.
     * Method name format: {action}_app_{stage}
     *
     * @param string $action Action name
     * @param string $stage Stage name (load, data, output)
     */
    public function executeStage(string $action, string $stage): void;
}
