<?php

declare(strict_types=1);

namespace Blueprint\Engine\Lexer;

/**
 * Tag Tokenizer
 * 
 * Tokenizes control tags content inside {% %}.
 * 
 * @package Blueprint\Engine\Lexer
 */
class TagTokenizer
{
    private ExpressionTokenizer $expressionTokenizer;

    public function __construct()
    {
        $this->expressionTokenizer = new ExpressionTokenizer();
    }

    /**
     * Tokenize tag content
     */
    public function tokenize(string $content, TokenStream $stream): void
    {
        $content = trim($content);
        
        if ($content === '') {
            return;
        }
        
        // Split by whitespace to get tag name and arguments
        $parts = preg_split('/(\s+)/', $content, 2, PREG_SPLIT_DELIM_CAPTURE);
        
        // First part is tag name
        $tagName = strtolower(trim($parts[0]));
        $stream->addToken(TokenTypes::NAME, $tagName);
        
        // Process remaining parts
        if (count($parts) > 2) {
            $args = trim($parts[2] ?? '');
            if ($args !== '') {
                $this->tokenizeArguments($args, $stream);
            }
        }
    }

    /**
     * Tokenize tag arguments
     */
    private function tokenizeArguments(string $args, TokenStream $stream): void
    {
        $tokens = $this->splitArguments($args);
        
        foreach ($tokens as $token) {
            $token = trim($token);
            
            if ($token === '') {
                continue;
            }
            
            // Operator
            if ($this->isOperator($token)) {
                $stream->addToken(TokenTypes::OPERATOR, $token);
                continue;
            }
            
            // Punctuation
            if ($this->isPunctuation($token)) {
                $stream->addToken(TokenTypes::PUNCTUATION, $token);
                continue;
            }
            
            // Expression
            $this->expressionTokenizer->tokenize($token, $stream);
        }
    }

    /**
     * Split arguments into tokens
     */
    private function splitArguments(string $args): array
    {
        $tokens = [];
        $current = '';
        $depth = 0;
        $inString = false;
        $stringChar = '';
        
        for ($i = 0; $i < strlen($args); $i++) {
            $char = $args[$i];
            
            if (!$inString) {
                if ($char === '"' || $char === "'") {
                    $inString = true;
                    $stringChar = $char;
                    $current .= $char;
                } elseif ($char === '(' || $char === '[' || $char === '{') {
                    $depth++;
                    $current .= $char;
                } elseif ($char === ')' || $char === ']' || $char === '}') {
                    $depth--;
                    $current .= $char;
                } elseif ($depth === 0 && ($char === ',' || $char === '=' || $char === ':')) {
                    // Split by punctuation
                    if (trim($current) !== '') {
                        $tokens[] = trim($current);
                    }
                    $tokens[] = $char;
                    $current = '';
                } elseif (ctype_space($char) && $depth === 0) {
                    // Split by space
                    if (trim($current) !== '') {
                        $tokens[] = trim($current);
                        $current = '';
                    }
                } else {
                    $current .= $char;
                }
            } else {
                $current .= $char;
                if ($char === $stringChar && ($i === 0 || $args[$i - 1] !== '\\')) {
                    $inString = false;
                }
            }
        }
        
        if (trim($current) !== '') {
            $tokens[] = trim($current);
        }
        
        return $this->mergeOperators($tokens);
    }

    /**
     * Merge operator tokens
     */
    private function mergeOperators(array $tokens): array
    {
        $result = [];
        $i = 0;
        
        while ($i < count($tokens)) {
            $token = $tokens[$i];
            
            // Check for compound operators
            if (isset($tokens[$i + 1])) {
                $compound = $token . $tokens[$i + 1];
                
                if ($this->isOperator($compound)) {
                    $result[] = $compound;
                    $i += 2;
                    continue;
                }
            }
            
            $result[] = $token;
            $i++;
        }
        
        return $result;
    }

    /**
     * Check if token is operator
     */
    private function isOperator(string $token): bool
    {
        return in_array($token, [
            '=', '=>', '==', '!=', '<>', '===', '!==',
            '>', '<', '>=', '<=', 'and', 'or', 'not', 'in', 'is',
            '+', '-', '*', '/', '%', '||', '&&', '!', '?', ':',
        ], true);
    }

    /**
     * Check if token is punctuation
     */
    private function isPunctuation(string $token): bool
    {
        return in_array($token, [',', '(', ')', '[', ']', '{', '}', '.'], true);
    }
}
