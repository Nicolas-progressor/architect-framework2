<?php

declare(strict_types=1);

namespace Architect\Services\Errors\View;

/**
 * Base error view with common functionality.
 */
abstract class ErrorPageView
{
    /**
     * Render the error page.
     */
    abstract public function render(): void;

    /**
     * Clean output buffer.
     */
    protected function cleanOutputBuffer(): void
    {
        while (ob_get_level()) {
            ob_end_clean();
        }
    }

    /**
     * Escape HTML special characters.
     */
    protected function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Get common CSS styles.
     */
    protected function getCommonStyles(): string
    {
        return <<<'CSS'
            * { box-sizing: border-box; margin: 0; padding: 0; }
            body { 
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
                background: rgba(0,0,0,0.85); 
                min-height: 100vh;
                padding: 20px;
            }
            .error-overlay {
                max-width: 800px;
                margin: 40px auto;
                background: #fff;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            }
            .error-header {
                background: #dc2626;
                color: white;
                padding: 20px 25px;
                display: flex;
                align-items: center;
                gap: 12px;
            }
            .error-header h2 { font-size: 20px; font-weight: 600; }
            .error-body { padding: 25px; }
            .error-message {
                background: #f9fafb;
                padding: 20px;
                border-radius: 8px;
                font-family: 'Consolas', 'Monaco', monospace;
                font-size: 14px;
                color: #1f2937;
                white-space: pre-wrap;
                word-break: break-all;
                border: 1px solid #e5e7eb;
            }
            CSS;
    }

    /**
     * Get SVG icon.
     */
    protected function getIcon(): string
    {
        return '<svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';
    }

    /**
     * Render HTML document wrapper.
     */
    protected function renderDocument(string $title, string $content): void
    {
        echo <<<HTML
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <title>{$title}</title>
                <style>
                    {$this->getCommonStyles()}
                </style>
            </head>
            <body>
                {$content}
            </body>
            </html>
            HTML;
    }
}
