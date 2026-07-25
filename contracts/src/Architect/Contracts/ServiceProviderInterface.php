<?php

declare(strict_types=1);

namespace Architect\Contracts;

use Architect\Contracts\Core\ContainerInterface;

interface ServiceProviderInterface
{
    public function register(ContainerInterface $container): void;
    public function boot(ContainerInterface $container): void;
}
