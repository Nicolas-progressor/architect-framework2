<?php

declare(strict_types=1);

namespace Blueprint\Engine\Compiler;

/**
 * Expression Compiler
 * 
 * Compiles template expressions to PHP code.
 * Handles variables, properties, methods, filters, and operators.
 * 
 * @package Blueprint\Engine\Compiler
 */
class ExpressionCompiler
{
    /**
     * Compile expression to PHP code
     */
    public function compile(array $expr): string
    {
        $type = $expr['type'] ?? 'unknown';

        return match ($type) {
            'number' => $this->compileNumber($expr),
            'string' => $this->compileString($expr),
            'array' => $this->compileArray($expr),
            'variable' => $this->compileVariable($expr),
            'property' => $this->compileProperty($expr),
            'method' => $this->compileMethod($expr),
            'static_method' => $this->compileStaticMethod($expr),
            'static_property' => $this->compileStaticProperty($expr),
            'filter' => $this->compileFilter($expr),
            'binary' => $this->compileBinary($expr),
            'ternary' => $this->compileTernary($expr),
            'function' => $this->compileFunction($expr),
            default => 'null'
        };
    }

    /**
     * Compile number literal
     */
    protected function compileNumber(array $expr): string
    {
        $value = $expr['value'] ?? 0;
        
        // Return number as-is (not quoted)
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }
        
        // Handle string representation of number
        if (is_numeric($value)) {
            return (string) $value;
        }
        
        return '0';
    }

    /**
     * Compile string literal
     */
    protected function compileString(array $expr): string
    {
        $value = $expr['value'] ?? '';
        // Remove quotes if present
        $value = substr($value, 1, -1);
        // Escape special characters
        $value = addcslashes($value, "'\\");
        return "'" . $value . "'";
    }

    /**
     * Compile array literal
     */
    protected function compileArray(array $expr): string
    {
        $items = $expr['items'] ?? [];
        
        $phpItems = [];
        foreach ($items as $item) {
            if (isset($item['key'])) {
                // Associative array: key => value
                $key = $item['key'];
                if (preg_match('/^["\'](.*)["\']$/', $key)) {
                    $phpKey = $this->compileString(['value' => $key]);
                } else {
                    $phpKey = "'" . $key . "'";
                }
                $phpValue = $this->compile($item['value'] ?? []);
                $phpItems[] = $phpKey . ' => ' . $phpValue;
            } else {
                // Simple value
                $phpItems[] = $this->compile($item);
            }
        }
        
        return '[' . implode(', ', $phpItems) . ']';
    }

    /**
     * Compile variable reference
     */
    protected function compileVariable(array $expr): string
    {
        $name = $expr['name'] ?? '';
        
        // Built-in constants
        if ($name === 'true') return 'true';
        if ($name === 'false') return 'false';
        if ($name === 'null') return 'null';
        if ($name === 'loop') return '$__loop';

        return "\$__context['" . $name . "'] ?? null";
    }

    /**
     * Compile property access
     */
    protected function compileProperty(array $expr): string
    {
        $object = $this->compile($expr['object'] ?? []);
        $property = $expr['property'] ?? '';
        
        return "\$__runtime->getProperty(" . $object . ", '" . $property . "')";
    }

    /**
     * Compile method call
     */
    protected function compileMethod(array $expr): string
    {
        $object = $this->compile($expr['object'] ?? []);
        $method = $expr['method'] ?? '';
        
        $rawArgs = $this->normalizeArgs($expr['args'] ?? []);
        $args = implode(', ', array_map([$this, 'compile'], $rawArgs));

        return "\$__runtime->callMethod(" . $object . ", '" . $method . "', [" . $args . "])";
    }

    /**
     * Compile static method call
     */
    protected function compileStaticMethod(array $expr): string
    {
        $class = $expr['class'] ?? '';
        $method = $expr['method'] ?? '';
        
        $rawArgs = $this->normalizeArgs($expr['args'] ?? []);
        $args = implode(', ', array_map([$this, 'compile'], $rawArgs));

        // Use runtime for static method calls (supports extensions and custom handlers)
        return "\$__runtime->callStaticMethod('" . $class . "', '" . $method . "', [" . $args . "])";
    }

    /**
     * Compile static property access
     */
    protected function compileStaticProperty(array $expr): string
    {
        $class = $expr['class'] ?? '';
        $property = $expr['property'] ?? '';

        return "\\" . $class . "::\$" . $property;
    }

    /**
     * Compile filter application
     */
    protected function compileFilter(array $expr): string
    {
        $node = $this->compile($expr['node'] ?? []);
        $name = $expr['name'] ?? '';
        
        $rawArgs = $this->normalizeArgs($expr['args'] ?? []);
        $args = implode(', ', array_map([$this, 'compile'], $rawArgs));

        if ($args) {
            return "\$__runtime->applyFilter('" . $name . "', " . $node . ", [" . $args . "])";
        }
        
        return "\$__runtime->applyFilter('" . $name . "', " . $node . ")";
    }

    /**
     * Compile binary operation
     */
    protected function compileBinary(array $expr): string
    {
        $left = $this->compile($expr['left'] ?? []);
        $right = $this->compile($expr['right'] ?? []);
        $operator = $expr['operator'] ?? '';

        $phpOp = match ($operator) {
            'or' => '||',
            'and' => '&&',
            'not' => '!',
            'in' => 'in_array',
            'not in' => '!in_array',
            'is' => '===',
            'is not' => '!==',
            default => $operator
        };

        $left = "(" . $left . ")";
        $right = "(" . $right . ")";

        if (in_array($operator, ['in', 'not in'], true)) {
            return $phpOp . "(" . $left . ", " . $right . ")";
        }

        return $left . " " . $phpOp . " " . $right;
    }

    /**
     * Compile ternary expression
     */
    protected function compileTernary(array $expr): string
    {
        $condition = $this->compile($expr['condition'] ?? []);
        $trueExpr = $this->compile($expr['trueExpr'] ?? []);
        $falseExpr = $this->compile($expr['falseExpr'] ?? []);

        return "(" . $condition . ") ? (" . $trueExpr . ") : (" . $falseExpr . ")";
    }

    /**
     * Compile function call
     */
    protected function compileFunction(array $expr): string
    {
        $name = $expr['name'] ?? '';
        
        $rawArgs = $this->normalizeArgs($expr['args'] ?? []);
        $args = implode(', ', array_map([$this, 'compile'], $rawArgs));

        // Built-in functions
        if ($name === 'dump') {
            return "var_export(" . ($args ?: '$__context') . ", true)";
        }
        
        if ($name === 'range') {
            return "range(" . $args . ")";
        }
        
        return "\$__runtime->callFunction('" . $name . "', [" . $args . "], \$__context)";
    }

    /**
     * Normalize arguments array
     */
    protected function normalizeArgs(array $args): array
    {
        if (isset($args['args'])) {
            return $args['args'];
        }
        return $args;
    }

    /**
     * Check if expression contains raw filter
     */
    public function hasRawFilter(array $expr): bool
    {
        if (($expr['type'] ?? '') === 'filter' && ($expr['name'] ?? '') === 'raw') {
            return true;
        }
        
        if (isset($expr['node']) && is_array($expr['node'])) {
            return $this->hasRawFilter($expr['node']);
        }
        
        return false;
    }
}
