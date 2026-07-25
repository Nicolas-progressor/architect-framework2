<?php

declare(strict_types=1);

namespace Architect\Contracts\Core;

interface EnvironmentInterface
{
    public function getEnvironment(): string;
    public function isDevelopment(): bool;
    public function isTesting(): bool;
    public function isStaging(): bool;
    public function isProduction(): bool;
    public function get(string $key, mixed $default = null): mixed;
    public function all(): array;
}
