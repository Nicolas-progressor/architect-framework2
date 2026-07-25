<?php

declare(strict_types=1);

namespace Architect\Contracts;

/**
 * Base interface for all services.
 */
interface ServiceInterface
{
    /**
     * Boot the service.
     */
    public function boot(): void;
}
