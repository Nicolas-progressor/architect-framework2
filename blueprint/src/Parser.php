<?php

declare(strict_types=1);

namespace Blueprint\Engine;

use Blueprint\Engine\Parser\Parser as ModularParser;
use Blueprint\Engine\Parser\ParserContext;
use Blueprint\Engine\Parser\ExpressionParser;
use Blueprint\Engine\Parser\StatementParser;
use Blueprint\Engine\Parser\BodyParser;

/**
 * Parser - Template AST Parser
 * 
 * Facade for modular parser components.
 * Parses tokens from Lexer into an Abstract Syntax Tree (AST).
 * 
 * @package Blueprint\Engine
 */
class Parser
{
    /**
     * Parse tokens into AST
     * 
     * @param array $tokens Token array from Lexer
     * @return array AST
     * @throws Exception\BlueprintException
     */
    public function parse(array $tokens): array
    {
        $modularParser = new ModularParser();
        return $modularParser->parse($tokens);
    }
}
