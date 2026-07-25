<?php

declare(strict_types=1);

namespace Blueprint\Engine\Lexer;

use Blueprint\Engine\Exception\BlueprintException;

/**
 * Token Stream
 * 
 * Provides navigation over token stream with lookahead.
 * 
 * @package Blueprint\Engine\Lexer
 */
class TokenStream
{
    private int $position = 0;
    private int $line = 1;
    private int $column = 1;

    /**
     * @param Token[] $tokens
     */
    public function __construct(
        private array $tokens = []
    ) {}

    /**
     * Get current token
     */
    public function current(): ?Token
    {
        return $this->tokens[$this->position] ?? null;
    }

    /**
     * Peek token at offset
     */
    public function peek(int $offset = 1): ?Token
    {
        return $this->tokens[$this->position + $offset] ?? null;
    }

    /**
     * Advance and return current token
     */
    public function advance(): ?Token
    {
        $token = $this->current();
        $this->position++;
        return $token;
    }

    /**
     * Expect token of specific type
     */
    public function expect(string $type, ?string $value = null): Token
    {
        $token = $this->current();
        
        if ($token === null || !$token->is($type, $value)) {
            throw BlueprintException::syntaxError(
                sprintf(
                    'Expected token %s%s, got %s',
                    $type,
                    $value ? " '{$value}'" : '',
                    $token ? $token->type : 'null'
                ),
                null,
                $token?->line
            );
        }

        return $this->advance();
    }

    /**
     * Check if current token matches
     */
    public function match(string $type, ?string $value = null): bool
    {
        $token = $this->current();
        return $token !== null && $token->is($type, $value);
    }

    /**
     * Check if at end
     */
    public function isEnd(): bool
    {
        return $this->position >= count($this->tokens) - 1;
    }

    /**
     * Get position
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * Set position
     */
    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    /**
     * Add token to stream
     */
    public function add(Token $token): void
    {
        $this->tokens[] = $token;
    }

    /**
     * Add token from components
     */
    public function addToken(string $type, string $value): void
    {
        $this->tokens[] = new Token($type, $value, $this->line, $this->column);
        $this->updatePosition($value);
    }

    /**
     * Get all tokens
     * 
     * @return Token[]
     */
    public function getTokens(): array
    {
        return $this->tokens;
    }

    /**
     * Get tokens as arrays (for backward compatibility)
     */
    public function toArray(): array
    {
        return array_map(fn(Token $t) => $t->toArray(), $this->tokens);
    }

    /**
     * Update line/column position
     */
    private function updatePosition(string $value): void
    {
        for ($i = 0; $i < strlen($value); $i++) {
            if ($value[$i] === "\n") {
                $this->line++;
                $this->column = 1;
            } else {
                $this->column++;
            }
        }
    }

    /**
     * Skip whitespace tokens
     */
    public function skipWhitespace(): void
    {
        while ($this->match(TokenTypes::NAME) && $this->current()?->value === '') {
            $this->advance();
        }
    }
}
