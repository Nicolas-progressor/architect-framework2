<?php

declare(strict_types=1);

namespace Blueprint\Engine\Lexer;

/**
 * Template Tokenizer
 * 
 * Splits template source into TEXT and tag tokens.
 * Handles {{ }}, {% %}, {# #}, {!! !!} delimiters.
 * 
 * @package Blueprint\Engine\Lexer
 */
class TemplateTokenizer
{
    /**
     * Tokenize template source
     */
    public function tokenize(string $source, TokenStream $stream): void
    {
        $source = str_replace(array("\r\n", "\r"), "\n", $source);
        
        $pos = 0;
        $length = strlen($source);
        
        while ($pos < $length) {
            $next = $this->findNextTag($source, $pos);
            
            if ($next === null) {
                $text = substr($source, $pos);
                if ($text !== '') {
                    $stream->addToken(TokenTypes::TEXT, $text);
                }
                break;
            }
            
            if ($next['pos'] > $pos) {
                $text = substr($source, $pos, $next['pos'] - $pos);
                if ($text !== '') {
                    $stream->addToken(TokenTypes::TEXT, $text);
                }
            }
            
            $this->processTag($source, $next, $stream);
            
            $pos = $next['end'];
        }
        
        $stream->add(Token::eof($stream->current()?->line ?? 1, $stream->current()?->column ?? 1));
    }

    /**
     * Find next tag position
     */
    private function findNextTag(string $source, int $from): ?array
    {
        $tagTypes = array(
            'comment'  => array('start' => '{#', 'end' => '#}'),
            'raw'      => array('start' => '{!!', 'end' => '!!}'),
            'variable' => array('start' => '{{', 'end' => '}}'),
            'tag'      => array('start' => '{%', 'end' => '%}'),
        );
        
        $minPos = PHP_INT_MAX;
        $found = null;
        
        foreach ($tagTypes as $type => $delimiters) {
            $pos = strpos($source, $delimiters['start'], $from);
            
            if ($pos !== false && $pos < $minPos) {
                $minPos = $pos;
                $found = array(
                    'type' => $type,
                    'pos' => $pos,
                    'start' => $delimiters['start'],
                    'end' => $delimiters['end'],
                );
            }
        }
        
        if ($found === null) {
            return null;
        }
        
        $endPos = strpos($source, $found['end'], $found['pos'] + strlen($found['start']));
        
        if ($endPos === false) {
            return null;
        }
        
        $found['end'] = $endPos + strlen($found['end']);
        $found['innerStart'] = $found['pos'] + strlen($found['start']);
        $found['innerEnd'] = $endPos;
        
        return $found;
    }

    /**
     * Process found tag
     */
    private function processTag(string $source, array $tag, TokenStream $stream): void
    {
        $inner = substr($source, $tag['innerStart'], $tag['innerEnd'] - $tag['innerStart']);
        
        switch ($tag['type']) {
            case 'comment':
                $this->processComment($inner, $stream);
                break;
            case 'raw':
                $this->processRaw($inner, $stream);
                break;
            case 'variable':
                $this->processVariable($inner, $stream);
                break;
            case 'tag':
                $this->processControlTag($inner, $source, $tag, $stream);
                break;
        }
    }

    /**
     * Process comment tag {# #}
     */
    private function processComment(string $inner, TokenStream $stream): void
    {
        $stream->addToken(TokenTypes::COMMENT_START, '{#');
        if ($inner !== '') {
            $stream->addToken(TokenTypes::TEXT, $inner);
        }
        $stream->addToken(TokenTypes::COMMENT_END, '#}');
    }

    /**
     * Process raw output {!! !!}
     */
    private function processRaw(string $inner, TokenStream $stream): void
    {
        $stream->addToken(TokenTypes::RAW_START, '{!!');
        if ($inner !== '') {
            $stream->addToken(TokenTypes::TEXT, $inner);
        }
        $stream->addToken(TokenTypes::RAW_END, '!!}');
    }

    /**
     * Process variable output {{ }}
     */
    private function processVariable(string $inner, TokenStream $stream): void
    {
        $stream->addToken(TokenTypes::VARIABLE_START, '{{');
        if (trim($inner) !== '') {
            $stream->addToken(TokenTypes::TEXT, $inner);
        }
        $stream->addToken(TokenTypes::VARIABLE_END, '}}');
    }

    /**
     * Process control tag {% %}
     */
    private function processControlTag(string $inner, string $source, array $tag, TokenStream $stream): void
    {
        $innerTrimmed = trim($inner);
        
        if ($innerTrimmed === 'raw') {
            $this->processRawBlock($source, $tag, $stream);
            return;
        }
        
        $stream->addToken(TokenTypes::TAG_START, '{%');
        if (trim($inner) !== '') {
            $stream->addToken(TokenTypes::TEXT, $inner);
        }
        $stream->addToken(TokenTypes::TAG_END, '%}');
    }

    /**
     * Process {% raw %}...{% endraw %} block
     */
    private function processRawBlock(string $source, array $tag, TokenStream $stream): void
    {
        $searchPos = $tag['end'];
        $endrawPos = null;
        
        while ($searchPos < strlen($source)) {
            $nextTag = strpos($source, '{%', $searchPos);
            
            if ($nextTag === false) {
                break;
            }
            
            $tagContent = substr($source, $nextTag + 2, 20);
            
            if (preg_match('/^\s*endraw\s*%}/', $tagContent)) {
                $endrawPos = $nextTag;
                break;
            }
            
            $searchPos = $nextTag + 2;
        }
        
        if ($endrawPos !== null) {
            $rawContent = substr($source, $tag['end'], $endrawPos - $tag['end']);
            if ($rawContent !== '') {
                $stream->addToken(TokenTypes::TEXT, $rawContent);
            }
        } else {
            $rawContent = substr($source, $tag['end']);
            if ($rawContent !== '') {
                $stream->addToken(TokenTypes::TEXT, $rawContent);
            }
        }
    }
}
