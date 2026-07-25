<?php

declare(strict_types=1);

namespace Blueprint\Engine\Parser;

use Blueprint\Engine\Exception\BlueprintException;
use Blueprint\Engine\Lexer\TokenTypes;

/**
 * Statement Parser
 * 
 * Parses control structures: if, for, foreach, block, extends, etc.
 * 
 * @package Blueprint\Engine\Parser
 */
class StatementParser
{
    private ParserContext $context;
    private ExpressionParser $expressionParser;
    private BodyParser $bodyParser;

    public function __construct(ParserContext $context, ExpressionParser $expressionParser, BodyParser $bodyParser)
    {
        $this->context = $context;
        $this->expressionParser = $expressionParser;
        $this->bodyParser = $bodyParser;
    }

    /**
     * Parse statement by tag name
     */
    public function parse(string $tagName, int $line): array
    {
        return match ($tagName) {
            'if' => $this->parseIf($line),
            'elseif' => $this->parseElseIf($line),
            'else' => $this->parseElse($line),
            'endif' => $this->parseEndIf($line),
            'for' => $this->parseFor($line),
            'endfor' => $this->parseEndFor($line),
            'foreach' => $this->parseForeach($line),
            'endforeach' => $this->parseEndForeach($line),
            'block' => $this->parseBlock($line),
            'endblock' => $this->parseEndBlock($line),
            'extends' => $this->parseExtends($line),
            'include' => $this->parseInclude($line),
            'element' => $this->parseElement($line, 'element'),
            'widget' => $this->parseElement($line, 'widget'),
            'raw' => $this->parseRaw($line),
            'endraw' => $this->parseEndRaw($line),
            'set' => $this->parseSet($line),
            'macro' => $this->parseMacro($line),
            'endmacro' => $this->parseEndMacro($line),
            'filter' => $this->parseFilter($line),
            'endfilter' => $this->parseEndFilter($line),
            'layout' => $this->parseLayout($line),
            'section' => $this->parseSection($line),
            'endsection' => $this->parseEndSection($line),
            'yield' => $this->parseYield($line),
            'show' => $this->parseShow($line),
            default => $this->parseGenericTag($tagName, $line),
        };
    }

    // ============ IF Statement ============

    private function parseIf(int $line): array
    {
        $condition = $this->expressionParser->parse();
        $this->context->expect(TokenTypes::TAG_END);

        $body = $this->bodyParser->parse(['elseif', 'else', 'endif']);

        $elseifs = [];
        $elseBody = null;

        while (!$this->context->isEnd()) {
            if (!$this->context->match(TokenTypes::TAG_START)) {
                break;
            }

            $this->context->expect(TokenTypes::TAG_START);
            $tagName = strtolower($this->context->current()[1] ?? '');

            if ($tagName === 'elseif') {
                $this->context->advance();
                $elseifCondition = $this->expressionParser->parse();
                $this->context->expect(TokenTypes::TAG_END);
                
                $elseIfBody = $this->bodyParser->parse(['elseif', 'else', 'endif']);
                $elseifs[] = [
                    'condition' => $elseifCondition,
                    'body' => $elseIfBody,
                ];
            } elseif ($tagName === 'else') {
                $this->context->advance();
                $this->context->expect(TokenTypes::TAG_END);
                $elseBody = $this->bodyParser->parse(['endif']);
                break;
            } elseif ($tagName === 'endif') {
                $this->context->advance();
                $this->context->expect(TokenTypes::TAG_END);
                break;
            } else {
                break;
            }
        }

        return NodeFactory::ifNode($condition, $body, $elseifs, $elseBody, $line);
    }

    private function parseElseIf(int $line): array
    {
        $this->context->expect(TokenTypes::TAG_END);
        return ['type' => 'elseif', 'line' => $line];
    }

    private function parseElse(int $line): array
    {
        $this->context->expect(TokenTypes::TAG_END);
        return ['type' => 'else', 'line' => $line];
    }

    private function parseEndIf(int $line): array
    {
        $this->context->expect(TokenTypes::TAG_END);
        return ['type' => 'endif', 'line' => $line];
    }

    // ============ FOR Statement ============

    private function parseFor(int $line): array
    {
        $itemVar = null;
        $keyVar = null;

        if ($this->context->match(TokenTypes::NAME)) {
            $itemToken = $this->context->advance();
            $itemVar = $itemToken[1];

            if ($this->context->match(TokenTypes::PUNCTUATION, ',')) {
                $this->context->advance();
                if ($this->context->match(TokenTypes::NAME)) {
                    $keyToken = $this->context->advance();
                    $keyVar = $keyToken[1];
                }
            }

            $this->context->expect(TokenTypes::OPERATOR, 'in');
        }

        $iterable = $this->expressionParser->parse();
        $this->context->expect(TokenTypes::TAG_END);

        $body = $this->bodyParser->parse(['endfor']);

        $this->consumeEndTag('endfor');

        return NodeFactory::forNode($itemVar, $keyVar, $iterable, $body, $line);
    }

    private function parseEndFor(int $line): array
    {
        $this->context->expect(TokenTypes::TAG_END);
        return ['type' => 'endfor', 'line' => $line];
    }

    // ============ FOREACH Statement ============

    private function parseForeach(int $line): array
    {
        $iterable = $this->expressionParser->parse();

        $this->context->expect(TokenTypes::NAME, 'as');

        $itemVar = null;
        $keyVar = null;

        if ($this->context->match(TokenTypes::NAME)) {
            $keyToken = $this->context->advance();
            $keyVar = $keyToken[1];
            
            if ($this->context->match(TokenTypes::OPERATOR, '=>')) {
                $this->context->advance();
                if ($this->context->match(TokenTypes::NAME)) {
                    $itemToken = $this->context->advance();
                    $itemVar = $itemToken[1];
                }
            } else {
                $itemVar = $keyVar;
                $keyVar = null;
            }
        }

        $this->context->expect(TokenTypes::TAG_END);

        $body = $this->bodyParser->parse(['endforeach']);

        $this->consumeEndTag('endforeach');

        return [
            'type' => 'foreach',
            'item' => $itemVar,
            'key' => $keyVar,
            'iterable' => $iterable,
            'body' => $body,
            'line' => $line,
        ];
    }

    private function parseEndForeach(int $line): array
    {
        $this->context->expect(TokenTypes::TAG_END);
        return ['type' => 'endforeach', 'line' => $line];
    }

    // ============ BLOCK Statement ============

    private function parseBlock(int $line): array
    {
        $name = null;
        
        if ($this->context->match(TokenTypes::NAME)) {
            $nameToken = $this->context->advance();
            $name = $nameToken[1];
        }
        
        $this->context->expect(TokenTypes::TAG_END);

        $body = $this->bodyParser->parse(['endblock']);

        $this->consumeEndTag('endblock');

        return NodeFactory::block($name, $body, $line);
    }

    private function parseEndBlock(int $line): array
    {
        $this->context->expect(TokenTypes::TAG_END);
        return ['type' => 'endblock', 'line' => $line];
    }

    // ============ EXTENDS Statement ============

    private function parseExtends(int $line): array
    {
        $template = $this->expressionParser->parse();
        $this->context->expect(TokenTypes::TAG_END);

        return NodeFactory::extendsNode($template, $line);
    }

    // ============ INCLUDE Statement ============

    private function parseInclude(int $line): array
    {
        $template = $this->expressionParser->parse();
        $this->context->expect(TokenTypes::TAG_END);

        return NodeFactory::includeNode($template, $line);
    }

    // ============ ELEMENT/WIDGET Statement ============

    private function parseElement(int $line, string $type): array
    {
        $name = null;
        
        if ($this->context->match(TokenTypes::NAME)) {
            $nameToken = $this->context->advance();
            $name = $nameToken[1];
        }
        
        $this->context->expect(TokenTypes::TAG_END);

        return NodeFactory::element($type, $name, $line);
    }

    // ============ RAW Statement ============

    private function parseRaw(int $line): array
    {
        $this->context->expect(TokenTypes::TAG_END);
        
        $content = '';
        
        while (!$this->context->isEnd()) {
            if ($this->context->match(TokenTypes::TAG_START)) {
                $peek = $this->context->peek(1);
                if ($peek && strtolower($peek[1] ?? '') === 'endraw') {
                    break;
                }
            }
            $token = $this->context->advance();
            if ($token) {
                $content .= $token[1];
            }
        }
        
        $this->consumeEndTag('endraw');

        return NodeFactory::raw($content, $line);
    }

    private function parseEndRaw(int $line): array
    {
        $this->context->expect(TokenTypes::TAG_END);
        return ['type' => 'endraw', 'line' => $line];
    }

    // ============ SET Statement ============

    private function parseSet(int $line): array
    {
        $targets = [];
        
        while ($this->context->match(TokenTypes::NAME)) {
            $targetToken = $this->context->advance();
            $targets[] = $targetToken[1];
            
            if ($this->context->match(TokenTypes::PUNCTUATION, ',')) {
                $this->context->advance();
            } else {
                break;
            }
        }
        
        $this->context->expect(TokenTypes::OPERATOR, '=');
        
        $value = $this->expressionParser->parse();
        $this->context->expect(TokenTypes::TAG_END);

        return NodeFactory::setNode($targets, $value, $line);
    }

    // ============ MACRO Statement ============

    private function parseMacro(int $line): array
    {
        $name = null;
        $params = [];
        
        if ($this->context->match(TokenTypes::NAME)) {
            $nameToken = $this->context->advance();
            $name = $nameToken[1];
        }
        
        if ($this->context->match(TokenTypes::PUNCTUATION, '(')) {
            $this->context->advance();
            
            while (!$this->context->match(TokenTypes::PUNCTUATION, ')')) {
                if ($this->context->match(TokenTypes::NAME)) {
                    $paramToken = $this->context->advance();
                    $params[] = ['name' => $paramToken[1]];
                    
                    if ($this->context->match(TokenTypes::PUNCTUATION, ',')) {
                        $this->context->advance();
                    }
                } else {
                    break;
                }
            }
            
            $this->context->expect(TokenTypes::PUNCTUATION, ')');
        }
        
        $this->context->expect(TokenTypes::TAG_END);

        $body = $this->bodyParser->parse(['endmacro']);

        $this->consumeEndTag('endmacro');

        return NodeFactory::macroNode($name, $params, $body, $line);
    }

    private function parseEndMacro(int $line): array
    {
        $this->context->expect(TokenTypes::TAG_END);
        return ['type' => 'endmacro', 'line' => $line];
    }

    // ============ FILTER Statement ============

    private function parseFilter(int $line): array
    {
        $name = null;
        
        if ($this->context->match(TokenTypes::NAME)) {
            $nameToken = $this->context->advance();
            $name = $nameToken[1];
        }
        
        $this->context->expect(TokenTypes::TAG_END);

        $body = $this->bodyParser->parse(['endfilter']);

        $this->consumeEndTag('endfilter');

        return [
            'type' => 'filter',
            'name' => $name,
            'body' => $body,
            'line' => $line,
        ];
    }

    private function parseEndFilter(int $line): array
    {
        $this->context->expect(TokenTypes::TAG_END);
        return ['type' => 'endfilter', 'line' => $line];
    }

    // ============ LAYOUT Statement ============

    private function parseLayout(int $line): array
    {
        $template = $this->expressionParser->parse();
        $this->context->expect(TokenTypes::TAG_END);

        return [
            'type' => 'layout',
            'template' => $template,
            'line' => $line,
        ];
    }

    // ============ SECTION Statement ============

    private function parseSection(int $line): array
    {
        $name = null;
        
        if ($this->context->match(TokenTypes::NAME)) {
            $nameToken = $this->context->advance();
            $name = $nameToken[1];
        }
        
        $this->context->expect(TokenTypes::TAG_END);

        $body = $this->bodyParser->parse(['endsection']);

        $this->consumeEndTag('endsection');

        return NodeFactory::section($name, $body, $line);
    }

    private function parseEndSection(int $line): array
    {
        $this->context->expect(TokenTypes::TAG_END);
        return ['type' => 'endsection', 'line' => $line];
    }

    // ============ YIELD Statement ============

    private function parseYield(int $line): array
    {
        $name = 'content';
        
        if ($this->context->match(TokenTypes::NAME)) {
            $nameToken = $this->context->advance();
            $name = $nameToken[1];
        } elseif ($this->context->match(TokenTypes::STRING)) {
            $nameToken = $this->context->advance();
            $name = trim($nameToken[1], '"\'');
        }
        
        $this->context->expect(TokenTypes::TAG_END);

        return NodeFactory::yieldNode($name, $line);
    }

    // ============ SHOW Statement ============

    private function parseShow(int $line): array
    {
        return $this->parseYield($line);
    }

    // ============ GENERIC TAG ============

    private function parseGenericTag(string $name, int $line): array
    {
        $args = [];
        
        while (!$this->context->match(TokenTypes::TAG_END) && !$this->context->isEnd()) {
            $args[] = $this->expressionParser->parse();
            
            if ($this->context->match(TokenTypes::PUNCTUATION, ',')) {
                $this->context->advance();
            }
        }
        
        $this->context->expect(TokenTypes::TAG_END);

        return [
            'type' => 'tag',
            'name' => $name,
            'args' => $args,
            'line' => $line,
        ];
    }

    // ============ Helpers ============

    /**
     * Consume end tag
     */
    private function consumeEndTag(string $tagName): void
    {
        if ($this->context->match(TokenTypes::TAG_START)) {
            $peek = $this->context->peek(1);
            if ($peek && strtolower($peek[1] ?? '') === $tagName) {
                $this->context->advance(); // consume {%
                $this->context->advance(); // consume tagname
                $this->context->expect(TokenTypes::TAG_END);
            }
        }
    }
}
