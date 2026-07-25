<?php

declare(strict_types=1);

namespace Blueprint\Engine\Lexer;

/**
 * Token Value Object
 * 
 * Immutable token representation.
 * 
 * @package Blueprint\Engine\Lexer
 */
final class Token
{
    public function __construct(
        public readonly string $type,
        public readonly string $value,
        public readonly int $line = 0,
        public readonly int $column = 0
    ) {}

    /**
     * Check if token matches type and optionally value
     */
    public function is(string $type, ?string $value = null): bool
    {
        if ($this->type !== $type) {
            return false;
        }
        
        return $value === null || $this->value === $value;
    }

    /**
     * Check if token is one of types
     */
    public function isOneOf(string ...$types): bool
    {
        return in_array($this->type, $types, true);
    }

    /**
     * Create EOF token
     */
    public static function eof(int $line = 0, int $column = 0): self
    {
        return new self(TokenTypes::EOF, '', $line, $column);
    }

    /**
     * Convert to array (for backward compatibility)
     */
    public function toArray(): array
    {
        return [$this->type, $this->value, $this->line, $this->column];
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data[0] ?? TokenTypes::EOF,
            $data[1] ?? '',
            $data[2] ?? 0,
            $data[3] ?? 0
        );
    }
}
