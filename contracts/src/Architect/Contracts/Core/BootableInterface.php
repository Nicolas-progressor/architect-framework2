<?php

declare(strict_types=1);

namespace Architect\Contracts\Core;

interface BootableInterface
{
    public function boot(): void;
}
