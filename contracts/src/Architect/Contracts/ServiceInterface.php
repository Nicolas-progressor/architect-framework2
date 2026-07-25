<?php

declare(strict_types=1);

namespace Architect\Contracts;

interface ServiceInterface
{
    public function boot(): void;
}
