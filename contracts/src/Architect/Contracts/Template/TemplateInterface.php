<?php

declare(strict_types=1);

namespace Architect\Contracts\Template;

interface TemplateInterface
{
    public function render(string $template, array $data = []): string;
    public function exists(string $template): bool;
    public function share(string $key, mixed $value): void;
}
