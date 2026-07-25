<?php

declare(strict_types=1);

namespace Architect\Core\Contracts;

interface BootableInterface
{
    /**
     * Boot the service.
     */
    public function boot(): void;
}