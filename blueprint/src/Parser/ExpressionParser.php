<?php

declare(strict_types=1);

namespace Blueprint\Engine\Parser;

use Blueprint\Engine\Exception\BlueprintException;
use Blueprint\Engine\Lexer\TokenTypes;

/**
 * Expression Parser
 * 
 * Parses expressions: variables, functions, operators, filters, etc.
 * 
 * @package Blueprint\Engine\Parser
 */
class ExpressionParser
{
    private ParserContext $context;

    public function __construct(ParserContext $context)
    {
        $this->context = $context;
    }

    /**
     * Parse expression
     */
    public function parse(int $precedence = 0): array
    {
        $left = $this->parsePrimary();
        
        // Check ternary operator
        if ($this->context->match(TokenTypes::OPERATOR, '?')) {
            return $this->parseTernary($left);
        }

        return $this->parseBinaryOperators($left, $precedence);
    }

    /**
     * Parse primary expression
     */
    private function parsePrimary(): array
    {
        $token = $this->context->current();
        
        if ($token === null) {
            throw BlueprintException::syntaxError(
                'Unexpected end of expression',
                null,
                $this->context->current()[2] ?? null
            );
        }

        // Dot means method chaining
        if ($token[0] === TokenTypes::PUNCTUATION && $token[1] === '.') {
            return ['type' => 'chain', 'line' => $token[2] ?? 0];
        }
        
        switch ($token[0]) {
            case TokenTypes::NUMBER:
                return $this->parseNumber();
            case TokenTypes::STRING:
                return $this->parseString();
            case TokenTypes::NAME:
                return $this->parseVariableOrFunction();
            case TokenTypes::PUNCTUATION:
                return $this->parsePunctuation();
            default:
                throw BlueprintException::syntaxError(
                    "Unexpected token: {$token[0]}",
                    null,
                    $token[2] ?? null
                );
        }
    }

    /**
     * Parse number
     */
    private function parseNumber(): array
    {
        $token = $this->context->advance();
        return NodeFactory::number($token[1], $token[2] ?? 0);
    }

    /**
     * Parse string
     */
    private function parseString(): array
    {
        $token = $this->context->advance();
        return NodeFactory::string($token[1], $token[2] ?? 0);
    }

    /**
     * Parse punctuation (parentheses, brackets)
     */
    private function parsePunctuation(): array
    {
        $token = $this->context->current();
        
        if ($token[1] === '(') {
            $this->context->advance();
            $expr = $this->parse();
            $this->context->expect(TokenTypes::PUNCTUATION, ')');
            return $expr;
        }

        if ($token[1] === '[') {
            return $this->parseArrayLiteral();
        }

        throw BlueprintException::syntaxError(
            "Unexpected punctuation: {$token[1]}",
            null,
            $token[2] ?? null
        );
    }

    /**
     * Parse array literal
     */
    private function parseArrayLiteral(): array
    {
        $token = $this->context->current();
        $this->context->advance();
        
        $items = [];
        
        while (!$this->context->match(TokenTypes::PUNCTUATION, ']')) {
            $items[] = $this->parse();
            
            if (!$this->context->match(TokenTypes::PUNCTUATION, ']')) {
                $this->context->expect(TokenTypes::PUNCTUATION, ',');
            }
        }
        
        $this->context->expect(TokenTypes::PUNCTUATION, ']');
        
        return NodeFactory::arrayNode($items, $token[2] ?? 0);
    }

    /**
     * Parse variable or function call
     */
    private function parseVariableOrFunction(): array
    {
        $nameToken = $this->context->advance();
        $name = $nameToken[1];
        $line = $nameToken[2] ?? 0;

        // Check for static method call (Class::method)
        if ($this->context->match(TokenTypes::OPERATOR, '::') || 
            $this->context->match(TokenTypes::PUNCTUATION, '::')) {
            return $this->parseStaticMethodCall($name, $line);
        }

        // Check filters
        if ($this->isFilterOperator()) {
            return $this->parseFilters(NodeFactory::variable($name, $line));
        }

        // Check function call
        if ($this->context->match(TokenTypes::PUNCTUATION, '(')) {
            return $this->parseFunctionCall($name, $line);
        }

        // Check property access
        if ($this->context->match(TokenTypes::PUNCTUATION, '.')) {
            return $this->parsePropertyAccess(NodeFactory::variable($name, $line));
        }
        
        return NodeFactory::variable($name, $line);
    }

    /**
     * Parse static method call (Class::method())
     */
    private function parseStaticMethodCall(string $className, int $line): array
    {
        $this->context->advance(); // consume '::'
        
        $methodToken = $this->context->expect(TokenTypes::NAME);
        $methodName = $methodToken[1];
        $methodLine = $methodToken[2] ?? 0;
        
        // Check if it's a method call with arguments
        if ($this->context->match(TokenTypes::PUNCTUATION, '(')) {
            $result = $this->parseArguments();
            $node = NodeFactory::staticMethod($className, $methodName, $result['args'], $line);
            
            if ($result['hasChain']) {
                $node = $this->parsePropertyAccess($node);
            }
            
            return $node;
        }
        
        // Static property access (rare but possible)
        return NodeFactory::staticProperty($className, $methodName, $line);
    }

    /**
     * Parse function call
     */
    private function parseFunctionCall(string $name, int $line): array
    {
        $result = $this->parseArguments();
        $node = NodeFactory::functionCall($name, $result['args'], $line);
        
        if ($result['hasChain']) {
            $node = $this->parsePropertyAccess($node);
        }
        
        return $node;
    }

    /**
     * Parse property access chain
     */
    public function parsePropertyAccess(?array $node): array
    {
        // Handle null node (method chaining)
        if ($node === null) {
            if ($this->context->match(TokenTypes::PUNCTUATION, '.')) {
                $this->context->advance();
            }

            $propToken = $this->context->expect(TokenTypes::NAME);
            $node = NodeFactory::property(
                ['type' => 'null'],
                $propToken[1],
                $propToken[2] ?? 0
            );

            if ($this->context->match(TokenTypes::PUNCTUATION, '(')) {
                $result = $this->parseArguments();
                $node = NodeFactory::method(
                    ['type' => 'null'],
                    $propToken[1],
                    $result['args'],
                    $propToken[2] ?? 0
                );
                
                if ($result['hasChain']) {
                    return $this->parsePropertyAccess($node);
                }
            }
        }

        // Continue chain
        while ($this->context->match(TokenTypes::PUNCTUATION, '.')) {
            $this->context->advance();
            
            $propToken = $this->context->expect(TokenTypes::NAME);
            $propName = $propToken[1];
            $propLine = $propToken[2] ?? 0;
            
            if ($this->context->match(TokenTypes::PUNCTUATION, '(')) {
                $result = $this->parseArguments();
                $node = NodeFactory::method($node, $propName, $result['args'], $propLine);
                
                if (!$result['hasChain'] && !$this->context->match(TokenTypes::PUNCTUATION, '.')) {
                    return $node;
                }
            } else {
                $node = NodeFactory::property($node, $propName, $propLine);
                
                if ($this->isFilterOperator()) {
                    $node = $this->parseFilters($node);
                }
            }
        }

        return $node;
    }

    /**
     * Parse filters
     */
    private function parseFilters(array $node): array
    {
        while ($this->isFilterOperator()) {
            $this->context->advance();

            $filterToken = $this->context->expect(TokenTypes::NAME);
            $filterName = $filterToken[1];
            $filterLine = $filterToken[2] ?? 0;

            $filterArgs = [];
            if ($this->context->match(TokenTypes::PUNCTUATION, '(')) {
                $result = $this->parseArguments();
                $filterArgs = $result['args'];
            }

            $node = NodeFactory::filter($node, $filterName, $filterArgs, $filterLine);
        }

        return $node;
    }

    /**
     * Parse function arguments
     */
    private function parseArguments(): array
    {
        $this->context->expect(TokenTypes::PUNCTUATION, '(');
        $args = [];
        $hasChain = false;

        while (!$this->context->match(TokenTypes::PUNCTUATION, ')')) {
            $expr = $this->parse();
            
            if (isset($expr['type']) && $expr['type'] === 'chain') {
                return ['args' => $args, 'hasChain' => true];
            }

            $args[] = $expr;
            
            if ($this->context->match(TokenTypes::PUNCTUATION, ')')) {
                break;
            }

            $this->context->expect(TokenTypes::PUNCTUATION, ',');
        }

        $this->context->expect(TokenTypes::PUNCTUATION, ')');
        
        // Check for chaining but DON'T consume the dot
        if ($this->context->match(TokenTypes::PUNCTUATION, '.')) {
            $hasChain = true;
        }
        
        return ['args' => $args, 'hasChain' => $hasChain];
    }

    /**
     * Parse ternary expression
     */
    private function parseTernary(array $condition): array
    {
        $this->context->advance(); // consume '?'
        $trueExpr = $this->parse();
        
        $this->context->expect(TokenTypes::OPERATOR, ':');
        $falseExpr = $this->parse();
        
        return NodeFactory::ternary($condition, $trueExpr, $falseExpr, $condition['line'] ?? 0);
    }

    /**
     * Parse binary operators
     */
    private function parseBinaryOperators(array $left, int $precedence): array
    {
        while (true) {
            $token = $this->context->current();
            
            if ($token === null || !$this->context->match(TokenTypes::OPERATOR)) {
                break;
            }

            if ($token[1] === ':') {
                break;
            }

            $op = $this->context->advance()[1];
            $right = $this->parse($precedence + 1);

            $left = NodeFactory::binary($op, $left, $right, $token[2] ?? 0);
        }

        return $left;
    }

    /**
     * Check if current token is filter operator
     */
    private function isFilterOperator(): bool
    {
        return $this->context->match(TokenTypes::PUNCTUATION, '|') 
            || $this->context->match(TokenTypes::OPERATOR, '|');
    }
}
