<?php

declare(strict_types=1);

namespace Blueprint\Engine\Parser;

use Blueprint\Engine\Exception\BlueprintException;

/**
 * Parser - Template AST Parser
 * 
 * Main facade that coordinates parsing components.
 * Parses tokens from Lexer into an Abstract Syntax Tree (AST).
 * 
 * @package Blueprint\Engine\Parser
 */
class Parser
{
    /**
     * Parse tokens into AST
     * 
     * @param array $tokens Token array from Lexer
     * @return array AST
     * @throws BlueprintException
     */
    public function parse(array $tokens): array
    {
        $context = new ParserContext($tokens);
        $expressionParser = new ExpressionParser($context);
        
        // Create bodyParser with null statementParser first
        $bodyParser = new BodyParser($context, $expressionParser, null);
        
        // Create statementParser with bodyParser reference
        $statementParser = new StatementParser($context, $expressionParser, $bodyParser);
        
        // Update bodyParser with statementParser
        $bodyParser->setStatementParser($statementParser);

        $body = $bodyParser->parse();

        // Verify all tokens processed
        if (!$context->isEnd() && !$context->match(\Blueprint\Engine\Lexer\TokenTypes::EOF)) {
            $token = $context->current();
            throw BlueprintException::syntaxError(
                "Unexpected token: {$token[0]}",
                null,
                $token[2] ?? null
            );
        }

        return $body;
    }
}
