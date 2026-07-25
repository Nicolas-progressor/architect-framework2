<?php

declare(strict_types=1);

namespace Axiom\Orm\Exception;

use Exception;

class AxiomException extends Exception
{
    /**
     * Create exception with context
     */
    public static function withContext(string $message, array $context = []): self
    {
        $contextStr = !empty($context) ? ' Context: ' . json_encode($context) : '';
        return new self($message . $contextStr);
    }
}
