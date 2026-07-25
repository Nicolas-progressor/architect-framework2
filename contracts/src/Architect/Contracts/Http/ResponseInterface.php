<?php

declare(strict_types=1);

namespace Architect\Contracts\Http;

interface ResponseInterface
{
    public function setStatusCode(int $code): static;
    public function setHeader(string $name, string $value): static;
    public function setContent(string $content): static;
    public function send(): void;
}
