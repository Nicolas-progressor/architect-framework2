<?php

declare(strict_types=1);

namespace Architect\Contracts\Http;

interface RequestInterface
{
    public function getMethod(): string;
    public function getUri(): string;
    public function getHeader(string $name): ?string;
    public function getQuery(string $key, mixed $default = null): mixed;
    public function getPost(string $key, mixed $default = null): mixed;
    public function hasHeader(string $name): bool;
}
