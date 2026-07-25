<?php

declare(strict_types=1);

namespace Blueprint\Engine;

use Blueprint\Engine\Compiler\ExpressionCompiler;
use Blueprint\Engine\Compiler\StatementCompiler;
use Blueprint\Engine\Compiler\PhpGenerator;
use Blueprint\Engine\Lexer\Lexer;

/**
 * Template Compiler (Facade)
 * 
 * Compiles Blueprint templates into optimized PHP code.
 * Delegates to specialized compilers:
 * - ExpressionCompiler for expressions
 * - StatementCompiler for control structures
 * - PhpGenerator for code generation
 * 
 * @package Blueprint\Engine
 */
class Compiler
{
    protected ExpressionCompiler $expressionCompiler;
    protected StatementCompiler $statementCompiler;
    protected PhpGenerator $phpGenerator;

    public function __construct()
    {
        $this->expressionCompiler = new ExpressionCompiler();
        $this->statementCompiler = new StatementCompiler($this->expressionCompiler);
        $this->phpGenerator = new PhpGenerator();
    }

    /**
     * Compile template source to PHP code
     */
    public function compile(string $source, string $templateName = ''): string
    {
        $lexer = new Lexer();
        $tokens = $lexer->tokenize($source);
        
        $parser = new Parser();
        $ast = $parser->parse($tokens);
        
        return $this->generatePhp($ast, $templateName);
    }

    /**
     * Compile AST to PHP code
     */
    public function compileAst(array $ast, string $templateName = ''): string
    {
        return $this->generatePhp($ast, $templateName);
    }

    /**
     * Generate PHP code from AST
     */
    protected function generatePhp(array $ast, string $templateName = ''): string
    {
        $this->statementCompiler->reset();

        $php = $this->phpGenerator->generateHeader($templateName);
        
        $bodyPhp = $this->compileNode($ast);
        
        $layout = $this->statementCompiler->getLayout();
        $extends = $this->statementCompiler->getExtends();
        $blocks = $this->statementCompiler->getBlocks();

        if ($layout) {
            $php .= $this->phpGenerator->wrapWithLayout($layout, $bodyPhp);
        } elseif ($extends) {
            $php .= $this->phpGenerator->wrapWithExtends($extends, $blocks, $bodyPhp);
        } else {
            $php .= $bodyPhp;
        }

        return $this->phpGenerator->optimize($php);
    }

    /**
     * Compile AST node to PHP code
     */
    protected function compileNode(array $node): string
    {
        $type = $node['type'] ?? 'unknown';

        return match ($type) {
            'body' => $this->compileBody($node),
            'text' => $this->statementCompiler->compileText($node),
            'print' => $this->statementCompiler->compilePrint($node),
            'if' => $this->statementCompiler->compileIf($node, $this->compileBody(...)),
            'for' => $this->statementCompiler->compileFor($node, $this->compileBody(...)),
            'foreach' => $this->statementCompiler->compileForeach($node, $this->compileBody(...)),
            'block' => $this->statementCompiler->compileBlock($node, $this->compileBody(...)),
            'extends' => $this->statementCompiler->compileExtends($node),
            'include' => $this->statementCompiler->compileInclude($node),
            'element' => $this->statementCompiler->compileElement($node),
            'widget' => $this->statementCompiler->compileWidget($node),
            'raw' => $this->statementCompiler->compileRaw($node),
            'set' => $this->statementCompiler->compileSet($node),
            'macro' => $this->statementCompiler->compileMacro($node, $this->compileBody(...)),
            'comment' => '',
            'filter' => $this->statementCompiler->compileFilterTag($node, $this->compileBody(...)),
            'tag' => $this->statementCompiler->compileGenericTag($node),
            'layout' => $this->statementCompiler->compileLayout($node),
            'section' => $this->statementCompiler->compileSection($node, $this->compileBody(...)),
            'yield' => $this->statementCompiler->compileYield($node),
            default => ''
        };
    }

    /**
     * Compile body node (container for other nodes)
     */
    protected function compileBody(array $node): string
    {
        $php = '';
        foreach ($node['nodes'] ?? [] as $childNode) {
            $php .= $this->compileNode($childNode);
        }
        return $php;
    }

    /**
     * Get expression compiler
     */
    public function getExpressionCompiler(): ExpressionCompiler
    {
        return $this->expressionCompiler;
    }

    /**
     * Get statement compiler
     */
    public function getStatementCompiler(): StatementCompiler
    {
        return $this->statementCompiler;
    }

    /**
     * Get PHP generator
     */
    public function getPhpGenerator(): PhpGenerator
    {
        return $this->phpGenerator;
    }
}

