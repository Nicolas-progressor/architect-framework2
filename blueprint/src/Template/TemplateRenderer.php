<?php

declare(strict_types=1);

namespace Blueprint\Engine\Template;

use Blueprint\Engine\Exception\BlueprintException;
use Blueprint\Engine\Loader;
use Blueprint\Engine\Compiler;

/**
 * Template Renderer
 * 
 * Handles template rendering, compilation, and caching.
 * 
 * @package Blueprint\Engine\Template
 */
class TemplateRenderer
{
    protected Loader $loader;
    protected Compiler $compiler;
    protected bool $debug;
    protected bool $showErrors;

    public function __construct(Loader $loader, Compiler $compiler, bool $debug = false, bool $showErrors = true)
    {
        $this->loader = $loader;
        $this->compiler = $compiler;
        $this->debug = $debug;
        $this->showErrors = $showErrors;
    }

    /**
     * Render template with context
     */
    public function render(string $template, array $context, ?object $blueprint = null): string
    {
        $cacheEnabled = $this->loader->isCacheEnabled();
        $cacheFresh = $cacheEnabled && $this->loader->isFresh($template);

        if ($cacheFresh) {
            return $this->evaluateCompiled($template, $context, $blueprint);
        }

        $phpCode = $this->compile($template);

        if ($cacheEnabled) {
            $this->saveToCache($template, $phpCode);
        }

        return $this->evaluate($phpCode, $context, $template, $blueprint);
    }

    /**
     * Render string template
     */
    public function renderString(string $source, array $context, ?object $blueprint = null): string
    {
        $phpCode = $this->compiler->compile($source, 'string');
        return $this->evaluate($phpCode, $context, 'string', $blueprint);
    }

    /**
     * Compile template to PHP code
     */
    public function compile(string $template): string
    {
        $source = $this->loader->getSource($template);
        return $this->compiler->compile($source, $template);
    }

    /**
     * Compile string to PHP code
     */
    public function compileString(string $source, string $name = 'string'): string
    {
        return $this->compiler->compile($source, $name);
    }

    /**
     * Evaluate compiled PHP code
     */
    protected function evaluate(string $phpCode, array $context, string $templateName, ?object $blueprint = null): string
    {
        ob_start();

        try {
            $__context = $context;
            $__template = $blueprint;
            
            eval('?>' . $phpCode);
            
            return ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            
            if ($this->debug) {
                throw BlueprintException::runtimeError(
                    $e->getMessage(),
                    $templateName,
                    $e->getLine(),
                    $this->getCodeSnippet($phpCode, $e->getLine())
                );
            }
            
            return $this->showErrors 
                ? $this->renderError($e, $templateName)
                : '';
        }
    }

    /**
     * Evaluate compiled template from cache
     */
    protected function evaluateCompiled(string $template, array $context, ?object $blueprint = null): string
    {
        $compiledPath = $this->loader->getCompiledPath($template);
        
        ob_start();

        try {
            $__context = $context;
            $__template = $blueprint;
            
            include $compiledPath;
            
            return ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            
            $this->loader->clearCacheFor($template);
            return $this->render($template, $context, $blueprint);
        }
    }

    /**
     * Save compiled code to cache
     */
    protected function saveToCache(string $template, string $phpCode): void
    {
        $compiledPath = $this->loader->getCompiledPath($template);
        $dir = dirname($compiledPath);
        
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents($compiledPath, $phpCode);
    }

    /**
     * Get code snippet for error display
     */
    protected function getCodeSnippet(string $code, int $line, int $context = 3): string
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

    /**
     * Render error message
     */
    protected function renderError(\Throwable $e, string $templateName): string
    {
        return sprintf(
            '<div style="background: #fee; border: 1px solid #fcc; padding: 10px; margin: 10px 0;">' .
            '<strong>Blueprint Error</strong> in "%s":<br>%s' .
            '</div>',
            htmlspecialchars($templateName),
            htmlspecialchars($e->getMessage())
        );
    }

    /**
     * Set debug mode
     */
    public function setDebug(bool $debug): self
    {
        $this->debug = $debug;
        return $this;
    }

    /**
     * Set show errors flag
     */
    public function setShowErrors(bool $showErrors): self
    {
        $this->showErrors = $showErrors;
        return $this;
    }
}
