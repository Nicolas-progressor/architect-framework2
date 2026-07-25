<?php

declare(strict_types=1);

namespace Architect\Services\Mvc\Context;

/**
 * MVC request context.
 *
 * Holds the current request state including module, controller,
 * action, and various flags. Replaces static state in Pattern service.
 *
 * @package Architect\Services\Mvc\Context
 */
class MvcContext
{
    /** @var bool Whether this is a global 404 */
    private bool $isGlobal404 = false;

    /** @var bool Whether this is a 404 error */
    private bool $is404Error = false;

    /** @var bool Whether current module is global */
    private bool $isGlobalModule = false;

    /** @var object|null Module bootstrap instance */
    private ?object $moduleBootstrap = null;

    /** @var string|null Current module name */
    private ?string $module = null;

    /** @var string|null Current controller name */
    private ?string $controller = null;

    /** @var string Current action name */
    private string $action = 'index';

    /**
     * Check if 404 error.
     *
     * @return bool
     */
    public function is404Error(): bool
    {
        return $this->is404Error;
    }

    /**
     * Set 404 error flag.
     *
     * @param bool $value Flag value
     * @return self
     */
    public function set404Error(bool $value = true): self
    {
        $this->is404Error = $value;
        return $this;
    }

    /**
     * Check if global 404.
     *
     * @return bool
     */
    public function isGlobal404(): bool
    {
        return $this->isGlobal404;
    }

    /**
     * Set global 404 flag.
     *
     * Also sets isGlobalModule to true when value is true.
     *
     * @param bool $value Flag value
     * @return self
     */
    public function setGlobal404(bool $value = true): self
    {
        $this->isGlobal404 = $value;
        if ($value) {
            $this->isGlobalModule = true;
        }
        return $this;
    }

    /**
     * Check if global module.
     *
     * @return bool
     */
    public function isGlobalModule(): bool
    {
        return $this->isGlobalModule;
    }

    /**
     * Set global module flag.
     *
     * @param bool $value Flag value
     * @return self
     */
    public function setGlobalModule(bool $value = true): self
    {
        $this->isGlobalModule = $value;
        return $this;
    }

    /**
     * Get module bootstrap.
     *
     * @return object|null
     */
    public function getModuleBootstrap(): ?object
    {
        return $this->moduleBootstrap;
    }

    /**
     * Set module bootstrap.
     *
     * @param object|null $bootstrap Bootstrap instance
     * @return self
     */
    public function setModuleBootstrap(?object $bootstrap): self
    {
        $this->moduleBootstrap = $bootstrap;
        return $this;
    }

    /**
     * Get current module.
     *
     * @return string|null
     */
    public function getModule(): ?string
    {
        return $this->module;
    }

    /**
     * Set current module.
     *
     * @param string|null $module Module name
     * @return self
     */
    public function setModule(?string $module): self
    {
        $this->module = $module;
        return $this;
    }

    /**
     * Get current controller.
     *
     * @return string|null
     */
    public function getController(): ?string
    {
        return $this->controller;
    }

    /**
     * Set current controller.
     *
     * @param string|null $controller Controller name
     * @return self
     */
    public function setController(?string $controller): self
    {
        $this->controller = $controller;
        return $this;
    }

    /**
     * Get current action.
     *
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * Set current action.
     *
     * @param string $action Action name
     * @return self
     */
    public function setAction(string $action): self
    {
        $this->action = $action;
        return $this;
    }

    /**
     * Reset context to initial state.
     *
     * Clears all flags and resets module, controller, action.
     *
     * @return self
     */
    public function reset(): self
    {
        $this->isGlobal404 = false;
        $this->is404Error = false;
        $this->isGlobalModule = false;
        $this->moduleBootstrap = null;
        $this->module = null;
        $this->controller = null;
        $this->action = 'index';
        return $this;
    }
}
