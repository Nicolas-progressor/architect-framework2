<?php

declare(strict_types=1);

namespace Blueprint\Engine\Parser;

/**
 * Node Factory
 * 
 * Creates AST nodes with consistent structure.
 * 
 * @package Blueprint\Engine\Parser
 */
final class NodeFactory
{
    /**
     * Create text node
     */
    public static function text(string $value, int $line = 0): array
    {
        return [
            'type' => 'text',
            'value' => $value,
            'line' => $line,
        ];
    }

    /**
     * Create print node
     */
    public static function print(array $expr, int $line = 0, bool $isRaw = false): array
    {
        return [
            'type' => 'print',
            'expr' => $expr,
            'line' => $line,
            'isRaw' => $isRaw,
        ];
    }

    /**
     * Create variable node
     */
    public static function variable(string $name, int $line = 0): array
    {
        return [
            'type' => 'variable',
            'name' => $name,
            'line' => $line,
        ];
    }

    /**
     * Create number node
     */
    public static function number(string $value, int $line = 0): array
    {
        return [
            'type' => 'number',
            'value' => $value,
            'line' => $line,
        ];
    }

    /**
     * Create string node
     */
    public static function string(string $value, int $line = 0): array
    {
        return [
            'type' => 'string',
            'value' => $value,
            'line' => $line,
        ];
    }

    /**
     * Create function call node
     */
    public static function functionCall(string $name, array $args, int $line = 0): array
    {
        return [
            'type' => 'function',
            'name' => $name,
            'args' => $args,
            'line' => $line,
        ];
    }

    /**
     * Create property access node
     */
    public static function property(array $object, string $property, int $line = 0): array
    {
        return [
            'type' => 'property',
            'object' => $object,
            'property' => $property,
            'line' => $line,
        ];
    }

    /**
     * Create method call node
     */
    public static function method(array $object, string $method, array $args, int $line = 0): array
    {
        return [
            'type' => 'method',
            'object' => $object,
            'method' => $method,
            'args' => $args,
            'line' => $line,
        ];
    }

    /**
     * Create static method call node
     */
    public static function staticMethod(string $class, string $method, array $args, int $line = 0): array
    {
        return [
            'type' => 'static_method',
            'class' => $class,
            'method' => $method,
            'args' => $args,
            'line' => $line,
        ];
    }

    /**
     * Create static property access node
     */
    public static function staticProperty(string $class, string $property, int $line = 0): array
    {
        return [
            'type' => 'static_property',
            'class' => $class,
            'property' => $property,
            'line' => $line,
        ];
    }

    /**
     * Create filter node
     */
    public static function filter(array $node, string $name, array $args, int $line = 0): array
    {
        return [
            'type' => 'filter',
            'node' => $node,
            'name' => $name,
            'args' => $args,
            'line' => $line,
        ];
    }

    /**
     * Create binary operator node
     */
    public static function binary(string $operator, array $left, array $right, int $line = 0): array
    {
        return [
            'type' => 'binary',
            'operator' => $operator,
            'left' => $left,
            'right' => $right,
            'line' => $line,
        ];
    }

    /**
     * Create ternary node
     */
    public static function ternary(array $condition, array $trueExpr, array $falseExpr, int $line = 0): array
    {
        return [
            'type' => 'ternary',
            'condition' => $condition,
            'trueExpr' => $trueExpr,
            'falseExpr' => $falseExpr,
            'line' => $line,
        ];
    }

    /**
     * Create array node
     */
    public static function arrayNode(array $items, int $line = 0): array
    {
        return [
            'type' => 'array',
            'items' => $items,
            'line' => $line,
        ];
    }

    /**
     * Create body node
     */
    public static function body(array $nodes): array
    {
        return [
            'type' => 'body',
            'nodes' => $nodes,
        ];
    }

    /**
     * Create if node
     */
    public static function ifNode(array $condition, array $body, array $elseifs = [], ?array $else = null, int $line = 0): array
    {
        return [
            'type' => 'if',
            'condition' => $condition,
            'body' => $body,
            'elseifs' => $elseifs,
            'else' => $else,
            'line' => $line,
        ];
    }

    /**
     * Create for node
     */
    public static function forNode(?string $item, ?string $key, array $iterable, array $body, int $line = 0): array
    {
        return [
            'type' => 'for',
            'item' => $item,
            'key' => $key,
            'iterable' => $iterable,
            'body' => $body,
            'line' => $line,
        ];
    }

    /**
     * Create block node
     */
    public static function block(?string $name, array $body, int $line = 0): array
    {
        return [
            'type' => 'block',
            'name' => $name,
            'body' => $body,
            'line' => $line,
        ];
    }

    /**
     * Create extends node
     */
    public static function extendsNode(array $template, int $line = 0): array
    {
        return [
            'type' => 'extends',
            'template' => $template,
            'line' => $line,
        ];
    }

    /**
     * Create include node
     */
    public static function includeNode(array $template, int $line = 0): array
    {
        return [
            'type' => 'include',
            'template' => $template,
            'line' => $line,
        ];
    }

    /**
     * Create set node
     */
    public static function setNode(array $targets, array $value, int $line = 0): array
    {
        return [
            'type' => 'set',
            'targets' => $targets,
            'value' => $value,
            'line' => $line,
        ];
    }

    /**
     * Create macro node
     */
    public static function macroNode(string $name, array $params, array $body, int $line = 0): array
    {
        return [
            'type' => 'macro',
            'name' => $name,
            'params' => $params,
            'body' => $body,
            'line' => $line,
        ];
    }

    /**
     * Create section node
     */
    public static function section(?string $name, array $body, int $line = 0): array
    {
        return [
            'type' => 'section',
            'name' => $name,
            'body' => $body,
            'line' => $line,
        ];
    }

    /**
     * Create yield node
     */
    public static function yieldNode(string $name, int $line = 0): array
    {
        return [
            'type' => 'yield',
            'name' => $name,
            'line' => $line,
        ];
    }

    /**
     * Create comment node
     */
    public static function comment(int $line = 0): array
    {
        return [
            'type' => 'comment',
            'line' => $line,
        ];
    }

    /**
     * Create raw node
     */
    public static function raw(string $content, int $line = 0): array
    {
        return [
            'type' => 'raw',
            'content' => $content,
            'line' => $line,
        ];
    }

    /**
     * Create element/widget node
     */
    public static function element(string $type, ?string $name, int $line = 0): array
    {
        return [
            'type' => $type,
            'name' => $name,
            'line' => $line,
        ];
    }
}
