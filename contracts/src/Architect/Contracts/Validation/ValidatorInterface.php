<?php

declare(strict_types=1);

namespace Architect\Contracts\Validation;

interface ValidatorInterface
{
    public function validate(array $data, array $rules): bool;
    public function errors(): array;
    public function firstError(): ?string;
    public function valid(): bool;
}
