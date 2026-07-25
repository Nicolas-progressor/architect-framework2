<?php

declare(strict_types=1);

namespace Blueprint\Engine\Compiler;

/**
 * PHP Code Generator
 * 
 * Generates PHP code structure for compiled templates.
 * Handles header generation, code wrapping, and optimization.
 * 
 * @package Blueprint\Engine\Compiler
 */
class PhpGenerator
{
    /**
     * Generate PHP header for compiled template
     */
    public function generateHeader(string $templateName): string
    {
        $php = "<?php\n";
        $php .= "/*\n";
        $php .= " * Blueprint compiled template: {$templateName}\n";
        $php .= " * Generated at: " . date('Y-m-d H:i:s') . "\n";
        $php .= " */\n\n";
        
        $php .= "\$__context = \$__context ?? [];\n";
        $php .= "\$__blocks = \$__blocks ?? [];\n";
        $php .= "\$__macros = \$__macros ?? [];\n";
        $php .= "\$__sections = \$__context['__sections'] ?? \$__sections ?? [];\n";
        $php .= "\$__runtime = \$__context['__runtime'] ?? null;\n";
        $php .= "\$__blueprint = \$__context['__blueprint'] ?? null;\n\n";
        
        return $php;
    }

    /**
     * Wrap body with layout template
     */
    public function wrapWithLayout(string $layout, string $bodyPhp): string
    {
        $php = "\n// Layout: {$layout}\n";
        
        if (str_contains($layout, '$')) {
            $php .= "\$__layoutTemplate = {$layout};\n";
        } else {
            $php .= "\$__layoutTemplate = '{$layout}';\n";
        }
        
        $php .= "\$__layoutSections = [];\n";
        $php .= "\$__layoutContent = '';\n\n";
        $php .= "// Capture sections and content\n";
        $php .= "ob_start();\n";
        $php .= $bodyPhp;
        $php .= "\$__layoutContent = ob_get_clean();\n\n";
        $php .= "// Render layout with sections\n";
        $php .= "if (\$__template->exists(\$__layoutTemplate)) {\n";
        $php .= "    \$__layoutContext = \$__context;\n";
        $php .= "    \$__layoutContext['__sections'] = \$__sections;\n";
        $php .= "    \$__layoutContext['__content'] = \$__layoutContent;\n";
        $php .= "    echo \$__template->render(\$__layoutTemplate, \$__layoutContext);\n";
        $php .= "} else {\n";
        $php .= "    echo \$__layoutContent;\n";
        $php .= "}\n";
        
        return $php;
    }

    /**
     * Wrap body with extends (inheritance)
     */
    public function wrapWithExtends(string $extends, array $blocks, string $bodyPhp): string
    {
        $php = "\n// Parent template: {$extends}\n";
        $php .= "\$__parentContent = '';\n";
        $php .= "ob_start();\n";
        $php .= $bodyPhp;
        $php .= "\$__parentContent = ob_get_clean();\n\n";
        
        $php .= "// Register blocks\n";
        foreach ($blocks as $name => $blockContent) {
            $php .= "\$__blocks['{$name}'] = " . var_export($blockContent, true) . ";\n";
        }
        
        $php .= "\n// Render parent template\n";
        $php .= "if (\$__blueprint && \$__blueprint->exists('{$extends}')) {\n";
        $php .= "    \$__parentContext = \$__context;\n";
        $php .= "    \$__parentContext['__blocks'] = \$__blocks;\n";
        $php .= "    echo \$__blueprint->render('{$extends}', \$__parentContext);\n";
        $php .= "} else {\n";
        $php .= "    echo \$__parentContent;\n";
        $php .= "}\n";
        
        return $php;
    }

    /**
     * Optimize generated PHP code
     */
    public function optimize(string $php): string
    {
        // Remove empty echo statements
        $php = preg_replace("/echo '';\n?/", '', $php);
        
        // Combine consecutive echo statements
        $php = preg_replace("/echo '([^']+)';\s+echo '([^']+)';/s", "echo '$1$2';", $php);
        
        // Remove redundant whitespace
        $php = preg_replace("/\n{3,}/", "\n\n", $php);
        
        return $php;
    }

    /**
     * Generate code snippet for error display
     */
    public function getCodeSnippet(string $code, int $line, int $context = 3): string
    {
        $lines = explode("\n", $code);
        $start = max(0, $line - $context - 1);
        $end = min(count($lines), $line + $context);
        
        $snippet = '';
        for ($i = $start; $i < $end; $i++) {
            $prefix = ($i + 1 === $line) ? '>>> ' : '    ';
            $snippet .= $prefix . ($i + 1) . ': ' . ($lines[$i] ?? '') . "\n";
        }
        
        return $snippet;
    }
}
