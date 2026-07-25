<?php

declare(strict_types=1);

namespace Blueprint\Engine\Lexer;

/**
 * Expression Tokenizer
 * 
 * Tokenizes expression strings inside {{ }} and {% %} tags.
 * Handles variables, strings, numbers, operators, filters, method calls.
 * 
 * @package Blueprint\Engine\Lexer
 */
class ExpressionTokenizer
{
    /**
     * Tokenize expression string
     */
    public function tokenize(string $expression, TokenStream $stream): void
    {
        $expression = trim($expression);
        
        if ($expression === '') {
            return;
        }
        
        // Split by pipes (filters) respecting nesting
        $parts = $this->splitByPipe($expression);
        
        // First part is the main value
        $this->tokenizeValue($parts[0], $stream);
        
        // Remaining parts are filters
        for ($i = 1; $i < count($parts); $i++) {
            $stream->addToken(TokenTypes::PUNCTUATION, '|');
            $this->tokenizeValue(trim($parts[$i]), $stream);
        }
    }

    /**
     * Split expression by pipe (|) respecting nesting and strings
     */
    private function splitByPipe(string $expression): array
    {
        $parts = [];
        $current = '';
        $depth = 0;
        $inString = false;
        $stringChar = '';
        
        for ($i = 0; $i < strlen($expression); $i++) {
            $char = $expression[$i];
            
            if (!$inString) {
                if ($char === '"' || $char === "'") {
                    $inString = true;
                    $stringChar = $char;
                } elseif ($char === '(' || $char === '[' || $char === '{') {
                    $depth++;
                } elseif ($char === ')' || $char === ']' || $char === '}') {
                    $depth--;
                } elseif ($char === '|' && $depth === 0) {
                    $parts[] = trim($current);
                    $current = '';
                    continue;
                }
            } else {
                if ($char === $stringChar && ($i === 0 || $expression[$i - 1] !== '\\')) {
                    $inString = false;
                }
            }
            
            $current .= $char;
        }
        
        $parts[] = trim($current);
        
        return $parts;
    }

    /**
     * Tokenize a value expression
     */
    private function tokenizeValue(string $value, TokenStream $stream): void
    {
        $value = trim($value);
        
        if ($value === '') {
            return;
        }
        
        // Skip standalone punctuation
        if (in_array($value, [',', '.', '!', '.!'], true)) {
            return;
        }
        
        // Number
        if (is_numeric($value)) {
            $stream->addToken(TokenTypes::NUMBER, $value);
            return;
        }
        
        // String
        if (preg_match('/^["\'](.*)["\']$/s', $value)) {
            $stream->addToken(TokenTypes::STRING, $value);
            return;
        }
        
        // Ternary operator with colon
        if ($this->hasTernaryColon($value)) {
            $this->tokenizeTernary($value, $stream);
            return;
        }
        
        // Array literal {key: value}
        if (preg_match('/^\{.*\}$/s', $value) && str_contains($value, ':')) {
            $this->tokenizeArrayLiteral($value, $stream);
            return;
        }
        
        // Static method call (Class::method())
        if ($this->isStaticMethodCall($value)) {
            $this->tokenizeStaticMethodCall($value, $stream);
            return;
        }
        
        // Function call
        if ($this->isFunctionCall($value)) {
            $this->tokenizeFunctionCall($value, $stream);
            return;
        }
        
        // Operator
        if ($this->isOperator($value)) {
            $stream->addToken(TokenTypes::OPERATOR, $value);
            return;
        }
        
        // Binary expression
        if ($this->hasBinaryOperator($value)) {
            $this->tokenizeBinaryExpression($value, $stream);
            return;
        }
        
        // Property access (obj.prop)
        if (str_contains($value, '.')) {
            $this->tokenizePropertyAccess($value, $stream);
            return;
        }
        
        // Simple name
        if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $value)) {
            $stream->addToken(TokenTypes::NAME, $value);
            return;
        }
        
        // Unknown - add as name
        $stream->addToken(TokenTypes::NAME, $value);
    }

    /**
     * Check if value is a static method call (Class::method)
     */
    private function isStaticMethodCall(string $value): bool
    {
        // Check for Class::method pattern (with or without parentheses)
        return (bool) preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*\s*::\s*[a-zA-Z_]/', $value);
    }

    /**
     * Tokenize static method call (Class::method())
     */
    private function tokenizeStaticMethodCall(string $value, TokenStream $stream): void
    {
        // Find the :: position
        $doubleColonPos = strpos($value, '::');
        if ($doubleColonPos === false) {
            $stream->addToken(TokenTypes::NAME, $value);
            return;
        }
        
        $class = trim(substr($value, 0, $doubleColonPos));
        $rest = trim(substr($value, $doubleColonPos + 2));
        
        $stream->addToken(TokenTypes::NAME, $class);
        $stream->addToken(TokenTypes::OPERATOR, '::');
        
        // Parse the rest (method and possible chaining)
        if ($rest !== '') {
            // Find method name
            if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)(.*)$/s', $rest, $matches)) {
                $method = $matches[1];
                $afterMethod = trim($matches[2]);
                
                $stream->addToken(TokenTypes::NAME, $method);
                
                // Check for method arguments
                if (str_starts_with($afterMethod, '(')) {
                    $closePos = $this->findMatchingParen($afterMethod, 0);
                    
                    if ($closePos !== false) {
                        $args = substr($afterMethod, 1, $closePos - 1);
                        
                        $stream->addToken(TokenTypes::PUNCTUATION, '(');
                        
                        if (trim($args) !== '') {
                            $this->tokenizeArguments($args, $stream);
                        }
                        
                        $stream->addToken(TokenTypes::PUNCTUATION, ')');
                        
                        // Check for method chaining after static call
                        $afterCall = trim(substr($afterMethod, $closePos + 1));
                        if (str_starts_with($afterCall, '.')) {
                            $stream->addToken(TokenTypes::PUNCTUATION, '.');
                            $afterCall = ltrim($afterCall, '.');
                            if ($afterCall !== '') {
                                $this->tokenizeValue($afterCall, $stream);
                            }
                        }
                    }
                } elseif (str_starts_with($afterMethod, '.')) {
                    // Property access or chaining without parentheses
                    $stream->addToken(TokenTypes::PUNCTUATION, '.');
                    $afterMethod = ltrim($afterMethod, '.');
                    if ($afterMethod !== '') {
                        $this->tokenizeValue($afterMethod, $stream);
                    }
                }
            }
        }
    }

    /**
     * Check if value is an operator
     */
    private function isOperator(string $value): bool
    {
        $operators = [
            '>=', '<=', '==', '!=', '<>', '===', '!==',
            '++', '--', '+', '-', '*', '/', '%',
            '||', '&&', '!', 'and', 'or', 'not', 'in', 'is', '?', ':',
        ];
        
        return in_array(strtolower($value), $operators, true);
    }

    /**
     * Check if value has ternary colon
     */
    private function hasTernaryColon(string $value): bool
    {
        if (!str_contains($value, ':')) {
            return false;
        }
        
        // Skip static method calls (Class::method)
        if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*\s*::\s*[a-zA-Z_]/', $value)) {
            return false;
        }
        
        // Check if colon is outside strings and brackets
        $depth = 0;
        $inString = false;
        $stringChar = '';
        
        for ($i = 0; $i < strlen($value); $i++) {
            $char = $value[$i];
            
            if (!$inString) {
                if ($char === '"' || $char === "'") {
                    $inString = true;
                    $stringChar = $char;
                } elseif ($char === '(' || $char === '[' || $char === '{') {
                    $depth++;
                } elseif ($char === ')' || $char === ']' || $char === '}') {
                    $depth--;
                } elseif ($char === ':' && $depth === 0) {
                    // Check if this looks like ternary (after string or value)
                    $before = trim(substr($value, 0, $i));
                    if (preg_match('/["\']$/', $before) || preg_match('/[a-zA-Z0-9_\]\)]$/', $before)) {
                        return true;
                    }
                }
            } else {
                if ($char === $stringChar && $value[$i - 1] !== '\\') {
                    $inString = false;
                }
            }
        }
        
        return false;
    }

    /**
     * Tokenize ternary expression
     */
    private function tokenizeTernary(string $value, TokenStream $stream): void
    {
        // Split by : respecting nesting
        $parts = $this->splitByColon($value);
        
        if (count($parts) >= 2) {
            $this->tokenizeValue($parts[0], $stream);
            $stream->addToken(TokenTypes::OPERATOR, ':');
            $this->tokenizeValue($parts[1], $stream);
        }
    }

    /**
     * Split by colon respecting nesting
     */
    private function splitByColon(string $value): array
    {
        $parts = [];
        $current = '';
        $depth = 0;
        $inString = false;
        $stringChar = '';
        
        for ($i = 0; $i < strlen($value); $i++) {
            $char = $value[$i];
            
            if (!$inString) {
                if ($char === '"' || $char === "'") {
                    $inString = true;
                    $stringChar = $char;
                } elseif ($char === '(' || $char === '[' || $char === '{') {
                    $depth++;
                } elseif ($char === ')' || $char === ']' || $char === '}') {
                    $depth--;
                } elseif ($char === ':' && $depth === 0) {
                    $parts[] = trim($current);
                    $current = '';
                    continue;
                }
            } else {
                if ($char === $stringChar && $value[$i - 1] !== '\\') {
                    $inString = false;
                }
            }
            
            $current .= $char;
        }
        
        $parts[] = trim($current);
        
        return $parts;
    }

    /**
     * Check if value is a function call
     */
    private function isFunctionCall(string $value): bool
    {
        return (bool) preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*\s*\(/', $value);
    }

    /**
     * Tokenize function call
     */
    private function tokenizeFunctionCall(string $value, TokenStream $stream): void
    {
        // Extract function name
        preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', $value, $matches);
        $name = $matches[1];
        $argsStart = strlen($matches[0]) - 1;
        
        // Find matching closing paren
        $closePos = $this->findMatchingParen($value, $argsStart);
        
        if ($closePos === false) {
            $stream->addToken(TokenTypes::NAME, $name);
            $stream->addToken(TokenTypes::PUNCTUATION, '(');
            $stream->addToken(TokenTypes::PUNCTUATION, ')');
            return;
        }
        
        $args = substr($value, $argsStart + 1, $closePos - $argsStart - 1);
        
        $stream->addToken(TokenTypes::NAME, $name);
        $stream->addToken(TokenTypes::PUNCTUATION, '(');
        
        if (trim($args) !== '') {
            $this->tokenizeArguments($args, $stream);
        }
        
        $stream->addToken(TokenTypes::PUNCTUATION, ')');
        
        // Check for method chaining
        $rest = trim(substr($value, $closePos + 1));
        if (str_starts_with($rest, '.')) {
            $stream->addToken(TokenTypes::PUNCTUATION, '.');
            $rest = ltrim($rest, '.');
            if ($rest !== '') {
                $this->tokenizeValue($rest, $stream);
            }
        }
    }

    /**
     * Tokenize function arguments
     */
    private function tokenizeArguments(string $args, TokenStream $stream): void
    {
        $parts = $this->splitByComma($args);
        
        foreach ($parts as $i => $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            
            $this->tokenizeValue($part, $stream);
            
            if ($i < count($parts) - 1) {
                $stream->addToken(TokenTypes::PUNCTUATION, ',');
            }
        }
    }

    /**
     * Split by comma respecting nesting
     */
    private function splitByComma(string $value): array
    {
        $parts = [];
        $current = '';
        $depth = 0;
        $inString = false;
        $stringChar = '';
        
        for ($i = 0; $i < strlen($value); $i++) {
            $char = $value[$i];
            
            if (!$inString) {
                if ($char === '"' || $char === "'") {
                    $inString = true;
                    $stringChar = $char;
                } elseif ($char === '(' || $char === '[' || $char === '{') {
                    $depth++;
                } elseif ($char === ')' || $char === ']' || $char === '}') {
                    $depth--;
                } elseif ($char === ',' && $depth === 0) {
                    $parts[] = $current;
                    $current = '';
                    continue;
                }
            } else {
                if ($char === $stringChar && $value[$i - 1] !== '\\') {
                    $inString = false;
                }
            }
            
            $current .= $char;
        }
        
        $parts[] = $current;
        
        return $parts;
    }

    /**
     * Find matching closing parenthesis
     */
    private function findMatchingParen(string $value, int $openPos): int|false
    {
        $depth = 0;
        $inString = false;
        $stringChar = '';
        
        for ($i = $openPos; $i < strlen($value); $i++) {
            $char = $value[$i];
            
            if (!$inString) {
                if ($char === '"' || $char === "'") {
                    $inString = true;
                    $stringChar = $char;
                } elseif ($char === '(' || $char === '[') {
                    $depth++;
                } elseif ($char === ')' || $char === ']') {
                    $depth--;
                    if ($depth === 0) {
                        return $i;
                    }
                }
            } else {
                if ($char === $stringChar && $value[$i - 1] !== '\\') {
                    $inString = false;
                }
            }
        }
        
        return false;
    }

    /**
     * Check if value has binary operator
     */
    private function hasBinaryOperator(string $value): bool
    {
        return (bool) preg_match(
            '/[a-zA-Z0-9_\'"\]\)]\s*(>=|<=|==|!=|<>|===|!==|\|\||&&|and|or|is|in|not)\s+[a-zA-Z0-9_\'"\[\(]/i',
            $value
        );
    }

    /**
     * Tokenize binary expression
     */
    private function tokenizeBinaryExpression(string $value, TokenStream $stream): void
    {
        // Find operator position
        if (!preg_match('/^(.+?)\s*(>=|<=|==|!=|<>|===|!==|\|\||&&|and|or|is\s+not|is|in|not)\s+(.+)$/is', $value, $matches)) {
            $stream->addToken(TokenTypes::NAME, $value);
            return;
        }
        
        $this->tokenizeValue(trim($matches[1]), $stream);
        $stream->addToken(TokenTypes::OPERATOR, trim($matches[2]));
        $this->tokenizeValue(trim($matches[3]), $stream);
    }

    /**
     * Tokenize property access
     */
    private function tokenizePropertyAccess(string $value, TokenStream $stream): void
    {
        $parts = explode('.', $value);
        
        foreach ($parts as $i => $part) {
            $part = trim($part);
            
            if ($part === '') {
                continue;
            }
            
            if ($i > 0) {
                $stream->addToken(TokenTypes::PUNCTUATION, '.');
            }
            
            // Check if part is method call
            if ($this->isFunctionCall($part)) {
                $this->tokenizeFunctionCall($part, $stream);
            } elseif (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $part)) {
                $stream->addToken(TokenTypes::NAME, $part);
            } else {
                $this->tokenizeValue($part, $stream);
            }
        }
    }

    /**
     * Tokenize array literal
     */
    private function tokenizeArrayLiteral(string $value, TokenStream $stream): void
    {
        $content = trim(substr($value, 1, -1));
        
        $stream->addToken(TokenTypes::PUNCTUATION, '[');
        
        if ($content !== '') {
            $items = $this->splitByComma($content);
            
            foreach ($items as $i => $item) {
                $item = trim($item);
                
                if ($item === '') {
                    continue;
                }
                
                // Check for key: value
                if (str_contains($item, ':') && !$this->hasTernaryColon($item)) {
                    $this->tokenizeArrayItem($item, $stream);
                } else {
                    $this->tokenizeValue($item, $stream);
                }
                
                if ($i < count($items) - 1) {
                    $stream->addToken(TokenTypes::PUNCTUATION, ',');
                }
            }
        }
        
        $stream->addToken(TokenTypes::PUNCTUATION, ']');
    }

    /**
     * Tokenize array item (key: value)
     */
    private function tokenizeArrayItem(string $item, TokenStream $stream): void
    {
        $colonPos = strpos($item, ':');
        $key = trim(substr($item, 0, $colonPos));
        $value = trim(substr($item, $colonPos + 1));
        
        // Key
        if (preg_match('/^["\'](.*)["\']$/s', $key)) {
            $stream->addToken(TokenTypes::STRING, $key);
        } else {
            $stream->addToken(TokenTypes::NAME, $key);
        }
        
        $stream->addToken(TokenTypes::OPERATOR, ':');
        
        // Value
        $this->tokenizeValue($value, $stream);
    }
}
