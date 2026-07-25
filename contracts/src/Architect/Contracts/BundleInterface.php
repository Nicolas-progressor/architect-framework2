<?php

declare(strict_types=1);

namespace Architect\Contracts;

use Architect\Contracts\Core\ContainerInterface;
use Architect\Contracts\Core\FrameworkInterface;

interface BundleInterface
{
    public function getName(): string;
    public function register(ContainerInterface $container): void;
    public function boot(ContainerInterface $container, FrameworkInterface $framework): void;
    public function shutdown(ContainerInterface $container, FrameworkInterface $framework): void;
}
