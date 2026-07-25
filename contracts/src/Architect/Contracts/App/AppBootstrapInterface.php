<?php

declare(strict_types=1);

namespace Architect\Contracts\App;

interface AppBootstrapInterface
{
    public function getAppName(): string;
}
