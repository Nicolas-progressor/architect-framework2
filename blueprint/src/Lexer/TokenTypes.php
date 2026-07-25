<?php

declare(strict_types=1);

namespace Blueprint\Engine\Lexer;

/**
 * Token Types Constants
 * 
 * @package Blueprint\Engine\Lexer
 */
final class TokenTypes
{
    public const TEXT = 'TEXT';
    public const VARIABLE_START = 'VAR_START';
    public const VARIABLE_END = 'VAR_END';
    public const RAW_START = 'RAW_START';
    public const RAW_END = 'RAW_END';
    public const TAG_START = 'TAG_START';
    public const TAG_END = 'TAG_END';
    public const COMMENT_START = 'COMMENT_START';
    public const COMMENT_END = 'COMMENT_END';
    public const STRING = 'STRING';
    public const NUMBER = 'NUMBER';
    public const NAME = 'NAME';
    public const OPERATOR = 'OPERATOR';
    public const PUNCTUATION = 'PUNCTUATION';
    public const EOF = 'EOF';
}
