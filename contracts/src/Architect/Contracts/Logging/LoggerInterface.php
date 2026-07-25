<?php

declare(strict_types=1);

namespace Architect\Contracts\Logging;

use Psr\Log\LoggerInterface as PsrLoggerInterface;

interface LoggerInterface extends PsrLoggerInterface
{
    public function channel(string $name): static;
    public function getChannels(): array;
}
