<?php

declare(strict_types=1);

namespace Blueprint\Engine;

/**
 * Interface for Blueprint Extensions
 * 
 * Extensions allow adding custom filters, functions, and behavior to Blueprint.
 * 
 * @package Blueprint\Engine
 */
interface BlueprintExtension
{
    /**
     * Register the extension with Blueprint
     * 
     * @param Blueprint $blueprint The Blueprint instance to register with
     * @return void
     */
    public function register(Blueprint $blueprint): void;
}
