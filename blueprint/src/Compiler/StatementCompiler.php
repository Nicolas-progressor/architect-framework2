<?php

declare(strict_types=1);

namespace Blueprint\Engine\Compiler;

/**
 * Statement Compiler
 * 
 * Compiles template statements (control structures, blocks, includes).
 * Handles if, for, foreach, block, extends, include, set, macro, etc.
 * 
 * @package Blueprint\Engine\Compiler
 */
class StatementCompiler
{
    protected ExpressionCompiler $expressionCompiler;
    protected int $indentation = 0;
    
    /** @var array<string, string> Compiled blocks for inheritance */
    protected array $blocks = [];
    
    /** @var string Parent template for inheritance */
    protected string $extends = '';
    
    /** @var string Layout template */
    protected string $layout = '';
    
    /** @var array<string, array> Macros */
    protected array $macros = [];

    public function __construct(ExpressionCompiler $expressionCompiler)
    {
        $this->expressionCompiler = $expressionCompiler;
    }

    /**
     * Reset state for new compilation
     */
    public function reset(): void
    {
        $this->blocks = [];
        $this->extends = '';
        $this->layout = '';
        $this->macros = [];
        $this->indentation = 0;
    }

    /**
     * Set indentation level
     */
    public function setIndentation(int $level): void
    {
        $this->indentation = $level;
    }

    /**
     * Get current indentation
     */
    public function getIndentation(): int
    {
        return $this->indentation;
    }

    /**
     * Get indent string
     */
    protected function indent(): string
    {
        return str_repeat('    ', $this->indentation);
    }

    /**
     * Get compiled blocks
     */
    public function getBlocks(): array
    {
        return $this->blocks;
    }

    /**
     * Get extends template
     */
    public function getExtends(): string
    {
        return $this->extends;
    }

    /**
     * Get layout template
     */
    public function getLayout(): string
    {
        return $this->layout;
    }

    /**
     * Get macros
     */
    public function getMacros(): array
    {
        return $this->macros;
    }

    /**
     * Compile if statement
     */
    public function compileIf(array $node, callable $compileBody): string
    {
        $condition = $this->expressionCompiler->compile($node['condition'] ?? []);
        $indent = $this->indent();

        $php = $indent . "if (" . $condition . "):\n";
        
        $this->indentation++;
        $php .= $compileBody($node['body'] ?? []);
        $this->indentation--;
        
        // elseif branches
        foreach ($node['elseifs'] ?? [] as $elseif) {
            $elseifCondition = $this->expressionCompiler->compile($elseif['condition'] ?? []);
            $php .= $indent . "elseif (" . $elseifCondition . "):\n";
            
            $this->indentation++;
            $php .= $compileBody($elseif['body'] ?? []);
            $this->indentation--;
        }

        // else branch
        if (!empty($node['else'])) {
            $php .= $indent . "else:\n";
            
            $this->indentation++;
            $php .= $compileBody($node['else'] ?? []);
            $this->indentation--;
        }

        $php .= $indent . "endif;\n";
        
        return $php;
    }

    /**
     * Compile for loop
     */
    public function compileFor(array $node, callable $compileBody): string
    {
        $item = $node['item'] ?? 'item';
        $key = $node['key'] ?? 'key';
        $iterable = $this->expressionCompiler->compile($node['iterable'] ?? []);
        $indent = $this->indent();

        $php = $indent . "\$__iterable = " . $iterable . ";\n";
        $php .= $indent . "if (is_array(\$__iterable) || \$__iterable instanceof \\Countable):\n";
        $php .= $indent . "    \$__count = count(\$__iterable);\n";
        $php .= $indent . "    \$__index = 0;\n";
        $php .= $indent . "    foreach (\$__iterable as \$__key => \$__value):\n";
        $php .= $indent . "        \$__loop = ['index' => \$__index, 'index0' => \$__index, 'length' => \$__count, 'first' => \$__index === 0, 'last' => \$__index === \$__count - 1];\n";
        
        if ($key) {
            $php .= $indent . "        \$__context['" . $key . "'] = \$__key;\n";
        }
        
        $php .= $indent . "        \$__context['" . $item . "'] = \$__value;\n";

        $this->indentation++;
        $php .= $compileBody($node['body'] ?? []);
        $this->indentation--;
        
        $php .= $indent . "        \$__index++;\n";
        $php .= $indent . "    endforeach;\n";
        $php .= $indent . "endif;\n";
        
        return $php;
    }

    /**
     * Compile foreach loop
     */
    public function compileForeach(array $node, callable $compileBody): string
    {
        $item = $node['item'] ?? 'item';
        $key = $node['key'] ?? null;
        $iterable = $this->expressionCompiler->compile($node['iterable'] ?? []);
        $indent = $this->indent();
        
        $php = $indent . "\$__iterable = " . $iterable . ";\n";
        $php .= $indent . "if (is_array(\$__iterable) || \$__iterable instanceof \\Traversable):\n";
        
        if ($key) {
            $php .= $indent . "    \$__count = is_array(\$__iterable) ? count(\$__iterable) : 0;\n";
            $php .= $indent . "    \$__index = 0;\n";
            $php .= $indent . "    foreach (\$__iterable as \$__key => \$__value):\n";
            $php .= $indent . "        \$__loop = ['index' => \$__index, 'index0' => \$__index, 'length' => \$__count, 'first' => \$__index === 0, 'last' => \$__index === \$__count - 1];\n";
            $php .= $indent . "        \$__context['" . $key . "'] = \$__key;\n";
            $php .= $indent . "        \$__context['" . $item . "'] = \$__value;\n";
        } else {
            $php .= $indent . "    foreach (\$__iterable as \$__value):\n";
            $php .= $indent . "        \$__context['" . $item . "'] = \$__value;\n";
        }

        $this->indentation++;
        $php .= $compileBody($node['body'] ?? []);
        $this->indentation--;
        
        if ($key) {
            $php .= $indent . "        \$__index++;\n";
        }
        
        $php .= $indent . "    endforeach;\n";
        $php .= $indent . "endif;\n";
        
        return $php;
    }

    /**
     * Compile block statement
     */
    public function compileBlock(array $node, callable $compileBody): string
    {
        $name = $node['name'] ?? '';
        $indent = $this->indent();

        // Save block for parent template
        $this->blocks[$name] = $compileBody($node['body'] ?? []);

        $php = $indent . "// Block: {$name}\n";
        $php .= $indent . "if (isset(\$__blocks['{$name}'])) {\n";
        $this->indentation++;
        $php .= $indent . "echo \$__blocks['{$name}'];\n";
        $this->indentation--;
        $php .= $indent . "} else {\n";
        $this->indentation++;
        $php .= $compileBody($node['body'] ?? []);
        $this->indentation--;
        $php .= $indent . "}\n";
        
        return $php;
    }

    /**
     * Compile extends statement
     */
    public function compileExtends(array $node): string
    {
        $template = $this->expressionCompiler->compile($node['template'] ?? []);
        $this->extends = $template;
        
        return "\n// Extends: " . $template . "\n";
    }

    /**
     * Compile include statement
     */
    public function compileInclude(array $node): string
    {
        $template = $this->expressionCompiler->compile($node['template'] ?? []);
        $indent = $this->indent();

        return $indent . "echo \$__template->render(" . $template . ", \$__context);\n";
    }

    /**
     * Compile element statement
     */
    public function compileElement(array $node): string
    {
        $name = $node['name'] ?? '';
        $indent = $this->indent();

        return $indent . "if (isset(\$__context['element'])) { \$__context['element']('" . $name . "'); }\n" .
               $indent . "elseif (isset(\$__template)) { \$__template->element('" . $name . "'); }\n";
    }

    /**
     * Compile widget statement
     */
    public function compileWidget(array $node): string
    {
        $name = $node['name'] ?? '';
        $indent = $this->indent();

        return $indent . "if (isset(\$__context['widget'])) { \$__context['widget']('" . $name . "'); }\n" .
               $indent . "elseif (isset(\$__context['element'])) { \$__context['element']('" . $name . "'); }\n" .
               $indent . "elseif (isset(\$__template)) { \$__template->element('" . $name . "'); }\n";
    }

    /**
     * Compile set statement
     */
    public function compileSet(array $node): string
    {
        $targets = $node['targets'] ?? [];
        $value = $node['value'] ?? null;
        $indent = $this->indent();

        if ($value === null) {
            return '';
        }

        $phpValue = $this->expressionCompiler->compile($value);
        $php = '';

        foreach ($targets as $target) {
            $php .= $indent . "\$__context['" . $target . "'] = " . $phpValue . ";\n";
        }

        return $php;
    }

    /**
     * Compile macro definition
     */
    public function compileMacro(array $node, callable $compileBody): string
    {
        $name = $node['name'] ?? '';
        $params = $node['params'] ?? [];
        $body = $node['body'] ?? [];
        
        $paramNames = [];
        foreach ($params as $param) {
            if (isset($param['name'])) {
                $paramNames[] = $param['name'];
            }
        }

        $this->macros[$name] = [
            'params' => $paramNames,
            'body' => $body
        ];

        $indent = $this->indent();
        
        $php = $indent . "\$__macros['{$name}'] = function(";
        $php .= implode(', ', array_map(fn($p) => '$' . $p, $paramNames));
        $php .= ") use (\$__context) {\n";
        
        $this->indentation++;
        
        $php .= $indent . "    \$__macroContext = \$__context;\n";
        
        foreach ($paramNames as $param) {
            $php .= $indent . "    \$__macroContext['" . $param . "'] = $" . $param . ";\n";
        }
        
        $php .= $compileBody($body);
        $this->indentation--;
        
        $php .= $indent . "};\n";

        return $php;
    }

    /**
     * Compile filter tag
     */
    public function compileFilterTag(array $node, callable $compileBody): string
    {
        $name = $node['name'] ?? '';
        $indent = $this->indent();
        
        $php = $indent . "ob_start();\n";
        
        $this->indentation++;
        $php .= $compileBody($node['body'] ?? []);
        $this->indentation--;
        
        $php .= $indent . "\$__filterContent = ob_get_clean();\n";
        $php .= $indent . "echo \$__runtime->applyFilter('" . $name . "', \$__filterContent);\n";
        
        return $php;
    }

    /**
     * Compile layout statement
     */
    public function compileLayout(array $node): string
    {
        $template = $node['template'] ?? '';
        
        if (is_array($template) && ($template['type'] ?? '') === 'string') {
            $templateValue = $template['value'] ?? '';
            $templateValue = trim($templateValue, '"\'');
            $this->layout = $templateValue;
        } else {
            $this->layout = $this->expressionCompiler->compile($template);
        }
        
        return "\n// Layout: " . (is_string($this->layout) ? $this->layout : 'dynamic') . "\n";
    }

    /**
     * Compile section statement
     */
    public function compileSection(array $node, callable $compileBody): string
    {
        $name = $node['name'] ?? 'content';
        $indent = $this->indent();

        $php = $indent . "// Section: {$name}\n";
        $php .= $indent . "ob_start();\n";
        
        $this->indentation++;
        $php .= $compileBody($node['body'] ?? []);
        $this->indentation--;
        
        $php .= $indent . "\$__sections['{$name}'] = ob_get_clean();\n";
        
        return $php;
    }

    /**
     * Compile yield statement
     */
    public function compileYield(array $node): string
    {
        $name = $node['name'] ?? 'content';
        $indent = $this->indent();

        $php = $indent . "// Yield: {$name}\n";
        $php .= $indent . "if (isset(\$__sections['{$name}'])) {\n";
        $php .= $indent . "    echo \$__sections['{$name}'];\n";
        $php .= $indent . "}\n";
        
        return $php;
    }

    /**
     * Compile raw output
     */
    public function compileRaw(array $node): string
    {
        $content = $node['content'] ?? '';
        $indent = $this->indent();
        
        if (str_contains($content, "\n") || str_contains($content, "  ")) {
            return $indent . "echo <<<'BLUEPRINT_RAW'\n" . $content . "\nBLUEPRINT_RAW;\n";
        }
        
        $content = addcslashes($content, "'\\");
        return $indent . "echo '" . $content . "';\n";
    }

    /**
     * Compile text output
     */
    public function compileText(array $node): string
    {
        $text = $node['value'] ?? '';
        $text = addcslashes($text, "'\\");
        
        if ($text === '') {
            return '';
        }
        
        return $this->indent() . "echo '" . $text . "';\n";
    }

    /**
     * Compile print statement
     */
    public function compilePrint(array $node): string
    {
        $expr = $node['expr'] ?? null;
        $isRaw = $node['isRaw'] ?? false;
        
        if ($expr === null) {
            return '';
        }

        $phpExpr = $this->expressionCompiler->compile($expr);
        $indent = $this->indent();
        
        $hasRawFilter = $this->expressionCompiler->hasRawFilter($expr);
        
        if ($isRaw || $hasRawFilter) {
            return $indent . "echo " . $phpExpr . ";\n";
        }

        return $indent . "echo \$__runtime->escape(" . $phpExpr . ");\n";
    }

    /**
     * Compile generic tag
     */
    public function compileGenericTag(array $node): string
    {
        $name = $node['name'] ?? '';
        $args = $node['args'] ?? [];
        
        $argsPhp = [];
        foreach ($args as $arg) {
            $argsPhp[] = $this->expressionCompiler->compile($arg);
        }
        
        $indent = $this->indent();
        
        return $indent . "echo \$__runtime->callFunction('" . $name . "', [" . implode(', ', $argsPhp) . "], \$__context);\n";
    }
}
