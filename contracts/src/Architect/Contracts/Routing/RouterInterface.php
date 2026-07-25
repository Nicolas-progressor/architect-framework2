<?php

declare(strict_types=1);

namespace Architect\Contracts\Routing;

interface RouterInterface
{
    public function loadRoutes(string $appDir): void;
    public function hasRoute(): bool;
    public function segment(int $index, string $default = ''): string;
    public function getModule(): string;
    public function getController(): string;
    public function getAction(): string;
    public function getParam(string $name, string $default = ''): string;
    public function getPath(): string;
}
