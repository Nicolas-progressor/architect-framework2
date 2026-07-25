<?php

declare(strict_types=1);

namespace Blueprint\Tests;

use Blueprint\Engine\Blueprint;
use Blueprint\Engine\Config\BlueprintConfig;

/**
 * Blueprint Test Runner
 * 
 * Simple test runner for Blueprint Template Engine.
 * 
 * @package Blueprint\Tests
 */
class BlueprintTestRunner
{
    private int $passed = 0;
    private int $failed = 0;
    private array $errors = [];

    /**
     * Run all tests
     */
    public function run(): void
    {
        echo "=== Blueprint Template Engine Tests ===\n\n";

        // Core tests
        $this->testBasicRendering();
        $this->testVariables();
        $this->testFilters();
        $this->testControlStructures();
        $this->testTemplateInheritance();
        $this->testLayouts();
        $this->testMacros();
        $this->testEscaping();
        $this->testCustomFilters();
        $this->testCustomFunctions();

        // Summary
        $this->printSummary();
    }

    /**
     * Test basic rendering
     */
    private function testBasicRendering(): void
    {
        $this->section('Basic Rendering');

        $blueprint = $this->createBlueprint();

        // Simple text
        $this->assertEqual(
            'Hello World',
            $blueprint->renderString('Hello World'),
            'Plain text rendering'
        );

        // Variable output
        $this->assertEqual(
            'Hello John',
            $blueprint->renderString('Hello {{ name }}', ['name' => 'John']),
            'Simple variable output'
        );

        // Multiple variables
        $this->assertEqual(
            'John is 25 years old',
            $blueprint->renderString('{{ name }} is {{ age }} years old', ['name' => 'John', 'age' => 25]),
            'Multiple variables'
        );
    }

    /**
     * Test variables
     */
    private function testVariables(): void
    {
        $this->section('Variables');

        $blueprint = $this->createBlueprint();

        // Array access
        $this->assertEqual(
            'John',
            $blueprint->renderString('{{ user.name }}', ['user' => ['name' => 'John']]),
            'Array property access'
        );

        // Nested array
        $this->assertEqual(
            'New York',
            $blueprint->renderString('{{ user.address.city }}', ['user' => ['address' => ['city' => 'New York']]]),
            'Nested array access'
        );

        // Object property
        $obj = new \stdClass();
        $obj->name = 'Alice';
        $this->assertEqual(
            'Alice',
            $blueprint->renderString('{{ user.name }}', ['user' => $obj]),
            'Object property access'
        );

        // Default value
        $this->assertEqual(
            'default',
            $blueprint->renderString('{{ missing | default("default") }}'),
            'Default filter for missing variable'
        );
    }

    /**
     * Test filters
     */
    private function testFilters(): void
    {
        $this->section('Filters');

        $blueprint = $this->createBlueprint();

        // Upper filter
        $this->assertEqual(
            'HELLO',
            $blueprint->renderString('{{ name | upper }}', ['name' => 'hello']),
            'Upper filter'
        );

        // Lower filter
        $this->assertEqual(
            'hello',
            $blueprint->renderString('{{ name | lower }}', ['name' => 'HELLO']),
            'Lower filter'
        );

        // Trim filter
        $this->assertEqual(
            'hello',
            $blueprint->renderString('{{ name | trim }}', ['name' => '  hello  ']),
            'Trim filter'
        );

        // Chained filters
        $this->assertEqual(
            'HELLO WORLD',
            $blueprint->renderString('{{ name | trim | upper }}', ['name' => '  hello world  ']),
            'Chained filters'
        );

        // Filter with arguments
        $this->assertEqual(
            'He...',
            $blueprint->renderString('{{ name | truncate(5) }}', ['name' => 'Hello World']),
            'Filter with arguments'
        );
    }

    /**
     * Test control structures
     */
    private function testControlStructures(): void
    {
        $this->section('Control Structures');

        $blueprint = $this->createBlueprint();

        // If statement
        $this->assertEqual(
            'yes',
            $blueprint->renderString('{% if show %}yes{% endif %}', ['show' => true]),
            'If statement (true)'
        );

        $this->assertEqual(
            '',
            $blueprint->renderString('{% if show %}yes{% endif %}', ['show' => false]),
            'If statement (false)'
        );

        // If-else
        $this->assertEqual(
            'no',
            $blueprint->renderString('{% if show %}yes{% else %}no{% endif %}', ['show' => false]),
            'If-else statement'
        );

        // For loop
        $this->assertEqual(
            '123',
            $blueprint->renderString('{% for item in items %}{{ item }}{% endfor %}', ['items' => [1, 2, 3]]),
            'For loop'
        );

        // Foreach with key
        $result = $blueprint->renderString(
            '{% foreach users as user %}{{ user.name }}{% endforeach %}',
            ['users' => [['name' => 'John'], ['name' => 'Jane']]]
        );
        $this->assertEqual(
            'JohnJane',
            $result,
            'Foreach loop'
        );
    }

    /**
     * Test template inheritance
     */
    private function testTemplateInheritance(): void
    {
        $this->section('Template Inheritance');

        // Note: Template inheritance requires file-based templates
        // This is a simplified test
        $blueprint = $this->createBlueprint();

        // Extends detection
        $compiled = $blueprint->compileString('{% extends "base.html" %}{% block title %}Home{% endblock %}');
        $this->assertTrue(
            str_contains($compiled, 'Parent template'),
            'Extends compilation'
        );

        // Block definition
        $compiled = $blueprint->compileString('{% block content %}Hello{% endblock %}');
        $this->assertTrue(
            str_contains($compiled, 'Block: content'),
            'Block compilation'
        );
    }

    /**
     * Test layouts
     */
    private function testLayouts(): void
    {
        $this->section('Layouts');

        $blueprint = $this->createBlueprint();

        // Layout detection
        $compiled = $blueprint->compileString('{% layout "main.html" %}{% section content %}Hello{% endsection %}');
        $this->assertTrue(
            str_contains($compiled, 'Layout: main.html'),
            'Layout compilation'
        );

        // Section definition
        $compiled = $blueprint->compileString('{% section sidebar %}Menu{% endsection %}');
        $this->assertTrue(
            str_contains($compiled, 'Section: sidebar'),
            'Section compilation'
        );

        // Yield
        $compiled = $blueprint->compileString('{% yield content %}');
        $this->assertTrue(
            str_contains($compiled, 'Yield: content'),
            'Yield compilation'
        );
    }

    /**
     * Test macros
     */
    private function testMacros(): void
    {
        $this->section('Macros');

        $blueprint = $this->createBlueprint();

        // Macro definition
        $compiled = $blueprint->compileString('{% macro input(name, value) %}<input name="{{ name }}" value="{{ value }}">{% endmacro %}');
        $this->assertTrue(
            str_contains($compiled, '__macros') || str_contains($compiled, 'input'),
            'Macro compilation'
        );
    }

    /**
     * Test escaping
     */
    private function testEscaping(): void
    {
        $this->section('Escaping');

        $blueprint = $this->createBlueprint();

        // HTML entities
        $result = $blueprint->renderString('{{ html }}', ['html' => '<script>alert("XSS")</script>']);
        $this->assertTrue(
            str_contains($result, '&lt;script&gt;'),
            'HTML escaping'
        );

        // Raw output
        $result = $blueprint->renderString('{!! html !!}', ['html' => '<b>bold</b>']);
        $this->assertEqual(
            '<b>bold</b>',
            $result,
            'Raw output'
        );

        // Raw filter
        $result = $blueprint->renderString('{{ html | raw }}', ['html' => '<b>bold</b>']);
        $this->assertEqual(
            '<b>bold</b>',
            $result,
            'Raw filter'
        );
    }

    /**
     * Test custom filters
     */
    private function testCustomFilters(): void
    {
        $this->section('Custom Filters');

        $blueprint = $this->createBlueprint();
        $blueprint->registerFilter('double', fn($v) => $v * 2);

        $this->assertEqual(
            '10',
            $blueprint->renderString('{{ value | double }}', ['value' => 5]),
            'Custom filter'
        );

        $blueprint->registerFilter('prefix', fn($v, $prefix) => $prefix . $v);

        $this->assertEqual(
            'pre-value',
            $blueprint->renderString('{{ value | prefix("pre-") }}', ['value' => 'value']),
            'Custom filter with argument'
        );
    }

    /**
     * Test custom functions
     */
    private function testCustomFunctions(): void
    {
        $this->section('Custom Functions');

        $blueprint = $this->createBlueprint();
        $blueprint->registerFunction('greet', fn($name) => "Hello, $name!");

        $this->assertEqual(
            'Hello, World!',
            $blueprint->renderString('{{ greet("World") }}'),
            'Custom function'
        );

        $blueprint->registerFunction('sum', fn($a, $b) => $a + $b);

        $this->assertEqual(
            '15',
            $blueprint->renderString('{{ sum(10, 5) }}'),
            'Custom function with multiple arguments'
        );
    }

    // ============ Helpers ============

    /**
     * Create Blueprint instance
     */
    private function createBlueprint(): Blueprint
    {
        $config = new BlueprintConfig([
            'debug' => true,
            'show_errors' => true,
            'cache_enabled' => false,
        ]);

        return new Blueprint($config);
    }

    /**
     * Assert equal
     */
    private function assertEqual(string $expected, string $actual, string $message): void
    {
        if ($expected === $actual) {
            $this->passed++;
            echo "  ✓ {$message}\n";
        } else {
            $this->failed++;
            $this->errors[] = $message;
            echo "  ✗ {$message}\n";
            echo "    Expected: {$expected}\n";
            echo "    Actual:   {$actual}\n";
        }
    }

    /**
     * Assert true
     */
    private function assertTrue(bool $condition, string $message): void
    {
        if ($condition) {
            $this->passed++;
            echo "  ✓ {$message}\n";
        } else {
            $this->failed++;
            $this->errors[] = $message;
            echo "  ✗ {$message}\n";
        }
    }

    /**
     * Print section header
     */
    private function section(string $name): void
    {
        echo "\n--- {$name} ---\n";
    }

    /**
     * Print summary
     */
    private function printSummary(): void
    {
        echo "\n=== Summary ===\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";

        if ($this->failed > 0) {
            echo "\nFailed tests:\n";
            foreach ($this->errors as $error) {
                echo "  - {$error}\n";
            }
        }

        echo "\n";
    }
}

