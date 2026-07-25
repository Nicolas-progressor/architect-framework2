<?php

declare(strict_types=1);

namespace Architect\Services\App\Contracts;

/**
 * Interface for application bootstrap classes.
 *
 * Application bootstraps can hook into the statement lifecycle
 * by implementing methods with naming convention: method_{statement}.
 */
interface AppBootstrapInterface
{
    /**
     * Get the application name this bootstrap belongs to.
     */
    public function getAppName(): string;
}
