<?php

declare(strict_types=1);

namespace Blueprint\Engine\Parser;

use Blueprint\Engine\Exception\BlueprintException;
use Blueprint\Engine\Lexer\TokenTypes;

/**
 * Body Parser
 * 
 * Parses body content - sequences of nodes.
 * 
 * @package Blueprint\Engine\Parser
 */
class BodyParser
{
    private ParserContext $context;
    private ExpressionParser $expressionParser;
    private ?StatementParser $statementParser = null;

    public function __construct(
        ParserContext $context,
        ExpressionParser $expressionParser,
        ?StatementParser $statementParser = null
    ) {
        $this->context = $context;
        $this->expressionParser = $expressionParser;
        $this->statementParser = $statementParser;
    }

    /**
     * Set statement parser
     */
    public function setStatementParser(StatementParser $parser): void
    {
        $this->statementParser = $parser;
    }

    /**
     * Parse body until stop tags
     */
    public function parse(array $stopTags = []): array
    {
        $nodes = [];
        $stopTagsLower = array_map('strtolower', $stopTags);

        while (!$this->context->isEnd()) {
            // Check stop tags
            if (!empty($stopTagsLower) && $this->context->match(TokenTypes::TAG_START)) {
                $peekToken = $this->context->peek(1);
                if ($peekToken && $peekToken[0] === TokenTypes::NAME) {
                    $tagName = strtolower($peekToken[1]);
                    if (in_array($tagName, $stopTagsLower, true)) {
                        return NodeFactory::body($nodes);
                    }
                }
            }

            if ($this->context->match(TokenTypes::EOF)) {
                break;
            }

            $this->context->enterDepth();
            $node = $this->parseNode();
            
            if ($node !== null) {
                $nodes[] = $node;
            }
            
            $this->context->exitDepth();
        }

        return NodeFactory::body($nodes);
    }

    /**
     * Parse single node
     */
    private function parseNode(): ?array
    {
        $token = $this->context->current();

        if ($token === null) {
            return null;
        }

        switch ($token[0]) {
            case TokenTypes::TEXT:
                return $this->parseText();
            case TokenTypes::RAW_START:
                return $this->parseRawPrint();
            case TokenTypes::VARIABLE_START:
                return $this->parseVariable();
            case TokenTypes::TAG_START:
                return $this->parseTag();
            case TokenTypes::COMMENT_START:
                return $this->parseComment();
            case TokenTypes::EOF:
                return null;
            default:
                $this->context->advance();
                return null;
        }
    }

    /**
     * Parse text node
     */
    private function parseText(): array
    {
        $token = $this->context->advance();
        return NodeFactory::text($token[1], $token[2] ?? 0);
    }

    /**
     * Parse raw print {!! !!}
     */
    private function parseRawPrint(): array
    {
        $this->context->expect(TokenTypes::RAW_START);
        $line = $this->context->current()[2] ?? 0;

        $expr = $this->expressionParser->parse();

        $this->context->expect(TokenTypes::RAW_END);

        return NodeFactory::print($expr, $line, true);
    }

    /**
     * Parse variable output {{ }}
     */
    private function parseVariable(): array
    {
        $this->context->expect(TokenTypes::VARIABLE_START);
        $line = $this->context->current()[2] ?? 0;
        
        $expr = $this->expressionParser->parse();

        // Handle chain marker
        if (isset($expr['type']) && $expr['type'] === 'chain') {
            $expr = $this->expressionParser->parsePropertyAccess(null);
        }

        $this->context->expect(TokenTypes::VARIABLE_END);

        return NodeFactory::print($expr, $line, false);
    }

    /**
     * Parse tag {% %}
     */
    private function parseTag(): array
    {
        $this->context->expect(TokenTypes::TAG_START);
        $line = $this->context->current()[2] ?? 0;

        $tagName = strtolower($this->context->expect(TokenTypes::NAME)[1] ?? '');

        if ($this->statementParser === null) {
            throw new \RuntimeException('StatementParser not set');
        }

        return $this->statementParser->parse($tagName, $line);
    }

    /**
     * Parse comment {# #}
     */
    private function parseComment(): array
    {
        $this->context->advance();
        $line = $this->context->current()[2] ?? 0;
        
        while (!$this->context->match(TokenTypes::COMMENT_END)) {
            $this->context->advance();
        }
        $this->context->advance();
        
        return NodeFactory::comment($line);
    }
}
