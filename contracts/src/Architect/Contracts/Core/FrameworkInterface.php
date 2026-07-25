<?php

declare(strict_types=1);

namespace Architect\Contracts\Core;

interface FrameworkInterface
{
    public function getContainer(): ContainerInterface;
    public function getStatement(): StatementInterface;
    public function boot(string $serviceId): void;
    public function bootAll(array $serviceIds): void;
    public function run(): void;
    public function get(string $id): mixed;
}
