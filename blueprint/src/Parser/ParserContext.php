<?php

declare(strict_types=1);

namespace Blueprint\Engine\Parser;

use Blueprint\Engine\Exception\BlueprintException;

/**
 * Parser Context
 * 
 * Manages token stream navigation during parsing.
 * 
 * @package Blueprint\Engine\Parser
 */
class ParserContext
{
    private array $tokens;
    private int $position = 0;
    private int $depth = 0;
    
    private const MAX_DEPTH = 50;

    /**
     * @param array $tokens Token array from Lexer
     */
    public function __construct(array $tokens)
    {
        $this->tokens = $tokens;
    }

    /**
     * Get current token
     */
    public function current(): ?array
    {
        return $this->tokens[$this->position] ?? null;
    }

    /**
     * Peek token at offset
     */
    public function peek(int $offset = 1): ?array
    {
        return $this->tokens[$this->position + $offset] ?? null;
    }

    /**
     * Advance and return current token
     */
    public function advance(): ?array
    {
        return $this->tokens[$this->position++] ?? null;
    }

    /**
     * Expect token of specific type
     */
    public function expect(string $type, ?string $value = null): array
    {
        $token = $this->current();
        
        if ($token === null || $token[0] !== $type) {
            throw BlueprintException::syntaxError(
                sprintf('Expected token %s, got %s', $type, $token[0] ?? 'null'),
                null,
                $token[2] ?? null
            );
        }

        if ($value !== null && $token[1] !== $value) {
            throw BlueprintException::syntaxError(
                sprintf("Expected value '%s', got '%s'", $value, $token[1]),
                null,
                $token[2] ?? null
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
        
        if ($token === null || $token[0] !== $type) {
            return false;
        }
        
        return $value === null || $token[1] === $value;
    }

    /**
     * Check if at end
     */
    public function isEnd(): bool
    {
        return $this->position >= count($this->tokens) - 1;
    }

    /**
     * Enter nesting level
     */
    public function enterDepth(): void
    {
        $this->depth++;
        
        if ($this->depth > self::MAX_DEPTH) {
            throw BlueprintException::syntaxError(
                'Maximum nesting depth exceeded',
                null,
                $this->current()[2] ?? null
            );
        }
    }

    /**
     * Exit nesting level
     */
    public function exitDepth(): void
    {
        $this->depth--;
    }

    /**
     * Get current position
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
     * Get tokens
     */
    public function getTokens(): array
    {
        return $this->tokens;
    }

    /**
     * Set tokens (for temporary parsing)
     */
    public function setTokens(array $tokens): void
    {
        $this->tokens = $tokens;
    }

    /**
     * Get token at specific position
     */
    public function getTokenAt(int $position): ?array
    {
        return $this->tokens[$position] ?? null;
    }

    /**
     * Count tokens
     */
    public function countTokens(): int
    {
        return count($this->tokens);
    }
}
