<?php

declare(strict_types=1);

namespace Blueprint\Engine\Lexer;

/**
 * Lexer - Template Tokenizer Facade
 * 
 * Coordinates tokenization components:
 * - TemplateTokenizer: splits template into TEXT and tags
 * - ExpressionTokenizer: tokenizes expressions inside {{ }}
 * - TagTokenizer: tokenizes control tags inside {% %}
 * 
 * @package Blueprint\Engine\Lexer
 */
class Lexer
{
    private TemplateTokenizer $templateTokenizer;
    private ExpressionTokenizer $expressionTokenizer;
    private TagTokenizer $tagTokenizer;

    public function __construct()
    {
        $this->templateTokenizer = new TemplateTokenizer();
        $this->expressionTokenizer = new ExpressionTokenizer();
        $this->tagTokenizer = new TagTokenizer();
    }

    /**
     * Tokenize template source
     * 
     * @param string $source Template source code
     * @return array Array of tokens (backward compatible format)
     */
    public function tokenize(string $source): array
    {
        $stream = new TokenStream();
        
        // First pass: split into TEXT and tags
        $this->templateTokenizer->tokenize($source, $stream);
        
        // Second pass: tokenize expressions inside tags
        return $this->tokenizeExpressions($stream);
    }

    /**
     * Second pass: tokenize expressions inside tags
     */
    private function tokenizeExpressions(TokenStream $stream): array
    {
        $tokens = $stream->getTokens();
        $newTokens = [];
        
        $i = 0;
        $count = count($tokens);
        
        while ($i < $count) {
            $token = $tokens[$i];
            
            // Check for variable tag {{ ... }}
            if ($token->is(TokenTypes::VARIABLE_START)) {
                $newTokens[] = $token->toArray();
                $i++;
                
                // Collect tokens until VARIABLE_END
                while ($i < $count && !$tokens[$i]->is(TokenTypes::VARIABLE_END)) {
                    if ($tokens[$i]->is(TokenTypes::TEXT)) {
                        // Tokenize expression content
                        $exprStream = new TokenStream();
                        $this->expressionTokenizer->tokenize($tokens[$i]->value, $exprStream);
                        foreach ($exprStream->getTokens() as $exprToken) {
                            $newTokens[] = $exprToken->toArray();
                        }
                    } else {
                        $newTokens[] = $tokens[$i]->toArray();
                    }
                    $i++;
                }
                
                if ($i < $count) {
                    $newTokens[] = $tokens[$i]->toArray(); // VARIABLE_END
                    $i++;
                }
            }
            // Check for control tag {% ... %}
            elseif ($token->is(TokenTypes::TAG_START)) {
                $newTokens[] = $token->toArray();
                $i++;
                
                // Collect tokens until TAG_END
                $tagContent = '';
                while ($i < $count && !$tokens[$i]->is(TokenTypes::TAG_END)) {
                    if ($tokens[$i]->is(TokenTypes::TEXT)) {
                        $tagContent .= $tokens[$i]->value;
                    }
                    $i++;
                }
                
                // Tokenize tag content
                if ($tagContent !== '') {
                    $tagStream = new TokenStream();
                    $this->tagTokenizer->tokenize($tagContent, $tagStream);
                    foreach ($tagStream->getTokens() as $tagToken) {
                        $newTokens[] = $tagToken->toArray();
                    }
                }
                
                if ($i < $count) {
                    $newTokens[] = $tokens[$i]->toArray(); // TAG_END
                    $i++;
                }
            }
            // Check for raw output {!! ... !!}
            elseif ($token->is(TokenTypes::RAW_START)) {
                $newTokens[] = $token->toArray();
                $i++;
                
                // Collect tokens until RAW_END
                while ($i < $count && !$tokens[$i]->is(TokenTypes::RAW_END)) {
                    if ($tokens[$i]->is(TokenTypes::TEXT)) {
                        // Tokenize expression content
                        $exprStream = new TokenStream();
                        $this->expressionTokenizer->tokenize($tokens[$i]->value, $exprStream);
                        foreach ($exprStream->getTokens() as $exprToken) {
                            $newTokens[] = $exprToken->toArray();
                        }
                    } else {
                        $newTokens[] = $tokens[$i]->toArray();
                    }
                    $i++;
                }
                
                if ($i < $count) {
                    $newTokens[] = $tokens[$i]->toArray(); // RAW_END
                    $i++;
                }
            }
            else {
                $newTokens[] = $token->toArray();
                $i++;
            }
        }
        
        return $newTokens;
    }
}
