<?php

declare(strict_types=1);

namespace Architect\Core\Environment;

interface EnvDetectorInterface
{
    /**
     * Detect current environment.
     */
    public function detect(): string;
}