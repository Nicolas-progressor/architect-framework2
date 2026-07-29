<?php

declare(strict_types=1);

namespace Tests\Services\Form;

use Architect\Services\Form\Interfaces\SessionInterface;
use PHPUnit\Framework\TestCase;

class ArraySession implements SessionInterface
{
    private array $data = [];

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function remove(string $key): void
    {
        unset($this->data[$key]);
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }
}
