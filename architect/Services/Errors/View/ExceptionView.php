<?php

declare(strict_types=1);

namespace Architect\Services\Errors\View;

use Throwable;

/**
 * Exception view - displays uncaught exceptions with Laravel-like styling.
 */
class ExceptionView extends ErrorPageView
{
    public function __construct(
        private readonly Throwable $exception,
        private readonly bool $debug = false
    ) {}

    public function render(): void
    {
        $this->cleanOutputBuffer();

        $errorId = bin2hex(random_bytes(8));
        $copyData = $this->debug ? $this->buildCopyData() : '{}';

        echo <<<HTML
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Исключение — Architect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        {$this->getStyles()}
    </style>
</head>
<body>
    <div class="error-page">
        <div class="error-container">
            <div class="error-header">
                <div class="error-icon">
                    <i class="bi bi-exclamation-octagon-fill"></i>
                </div>
                <h2>{$this->getShortClassName()}</h2>
            </div>
            
            <div class="error-body">
                <div class="error-message">{$this->escape($this->exception->getMessage())}</div>
                
                <div class="error-location">
                    <i class="bi bi-file-code"></i>
                    <span class="location-file">{$this->escape($this->exception->getFile())}</span>
                    <span class="location-line">:{$this->exception->getLine()}</span>
                </div>
                
                <div class="error-code">ERROR ID: {$errorId}</div>
                
                {$this->renderDebugInfo($errorId)}
            </div>
        </div>
    </div>
    
    <script>
        const errorData = {$copyData};
        
        function copyError() {
            const text = Object.entries(errorData)
                .map(([key, val]) => key + ': ' + val)
                .join('\n');
            navigator.clipboard.writeText(text).then(() => {
                const btn = document.getElementById('copyBtn');
                btn.innerHTML = '<i class="bi bi-check-lg"></i> Скопировано!';
                btn.classList.remove('btn-copy');
                btn.classList.add('btn-success');
                setTimeout(() => {
                    btn.innerHTML = '<i class="bi bi-clipboard"></i> Копировать';
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-copy');
                }, 2000);
            });
        }
        
        function toggleTrace() {
            const trace = document.getElementById('traceContent');
            const btn = document.getElementById('toggleTraceBtn');
            if (trace.style.display === 'none') {
                trace.style.display = 'block';
                btn.innerHTML = '<i class="bi bi-chevron-up"></i> Скрыть';
            } else {
                trace.style.display = 'none';
                btn.innerHTML = '<i class="bi bi-chevron-down"></i> Stack Trace';
            }
        }
        
        function togglePrevious() {
            const prev = document.getElementById('previousContent');
            const btn = document.getElementById('togglePrevBtn');
            if (prev.style.display === 'none') {
                prev.style.display = 'block';
                btn.innerHTML = '<i class="bi bi-chevron-up"></i> Скрыть';
            } else {
                prev.style.display = 'none';
                btn.innerHTML = '<i class="bi bi-chevron-down"></i> Previous';
            }
        }
    </script>
</body>
</html>
HTML;
    }

    /**
     * Get short class name without namespace.
     */
    private function getShortClassName(): string
    {
        $class = get_class($this->exception);
        $parts = explode('\\', $class);
        return end($parts);
    }

    /**
     * Build data for copy to clipboard.
     */
    private function buildCopyData(): string
    {
        $data = [
            'type' => get_class($this->exception),
            'message' => $this->exception->getMessage(),
            'file' => $this->exception->getFile(),
            'line' => $this->exception->getLine(),
            'trace' => $this->exception->getTraceAsString(),
            'error_id' => bin2hex(random_bytes(8))
        ];
        
        // Add previous exception if exists
        $previous = $this->exception->getPrevious();
        if ($previous !== false) {
            $data['previous'] = get_class($previous) . ': ' . $previous->getMessage();
        }
        
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Render debug information (Laravel-like).
     */
    private function renderDebugInfo(string $errorId): string
    {
        if (!$this->debug) {
            return '';
        }

        $trace = $this->exception->getTrace();
        $traceHtml = '';
        
        foreach ($trace as $i => $item) {
            $file = $item['file'] ?? 'unknown';
            $line = $item['line'] ?? 0;
            $function = $item['function'] ?? 'unknown';
            $class = $item['class'] ?? '';
            $type = $item['type'] ?? '';
            $args = $this->formatArgs($item['args'] ?? []);
            
            $fileName = basename($file);
            $lineNum = is_numeric($line) ? ":$line" : '';
            
            $traceHtml .= <<<HTML
<div class="trace-line">
    <span class="trace-num">#{$i}</span>
    <span class="trace-func">{$this->escape($class . $type . $function)}</span>
    <span class="trace-args">({$args})</span>
    <span class="trace-file">{$this->escape($fileName)}{$lineNum}</span>
</div>
HTML;
        }

        $previousHtml = $this->renderPreviousExceptions();

        return <<<HTML
<div class="debug-section">
    <button id="copyBtn" class="btn btn-copy" onclick="copyError()">
        <i class="bi bi-clipboard"></i> Копировать
    </button>
    
    <button id="toggleTraceBtn" class="btn btn-outline" onclick="toggleTrace()">
        <i class="bi bi-chevron-down"></i> Stack Trace
    </button>
    
    {$previousHtml}
    
    <div id="traceContent" class="trace-container" style="display: none;">
        <div class="trace-header">Stack Trace:</div>
        <div class="trace-content">{$traceHtml}</div>
    </div>
</div>
HTML;
    }

    /**
     * Render previous exceptions chain.
     */
    private function renderPreviousExceptions(): string
    {
        $previous = $this->exception->getPrevious();
        if ($previous === false) {
            return '';
        }

        $prevItems = [];
        $current = $previous;
        $depth = 0;
        
        while ($current !== false && $depth < 5) {
            $prevItems[] = [
                'class' => get_class($current),
                'message' => $current->getMessage(),
                'file' => $current->getFile(),
                'line' => $current->getLine()
            ];
            $current = $current->getPrevious();
            $depth++;
        }

        if (empty($prevItems)) {
            return '';
        }

        $prevHtml = '';
        foreach ($prevItems as $item) {
            $prevHtml .= <<<HTML
<div class="previous-item">
    <div class="previous-class">{$this->escape($item['class'])}</div>
    <div class="previous-message">{$this->escape($item['message'])}</div>
    <div class="previous-location">{$this->escape($item['file'])}:{$item['line']}</div>
</div>
HTML;
        }

        return <<<HTML
<button id="togglePrevBtn" class="btn btn-outline" onclick="togglePrevious()">
    <i class="bi bi-chevron-down"></i> Previous
</button>
<div id="previousContent" class="previous-container" style="display: none;">
    <div class="previous-header">Previous Exceptions:</div>
    {$prevHtml}
</div>
HTML;
    }

    /**
     * Format function arguments.
     */
    private function formatArgs(array $args): string
    {
        $formatted = [];
        foreach ($args as $arg) {
            if (is_null($arg)) {
                $formatted[] = 'null';
            } elseif (is_bool($arg)) {
                $formatted[] = $arg ? 'true' : 'false';
            } elseif (is_array($arg)) {
                $formatted[] = 'array[' . count($arg) . ']';
            } elseif (is_object($arg)) {
                $formatted[] = get_class($arg);
            } elseif (is_string($arg)) {
                $len = strlen($arg);
                $truncated = $len > 50 ? substr($arg, 0, 50) . '...' : $arg;
                $formatted[] = '"' . $this->escape($truncated) . '"';
            } elseif (is_resource($arg)) {
                $formatted[] = 'resource';
            } else {
                $formatted[] = (string) $arg;
            }
        }
        
        return implode(', ', $formatted);
    }

    /**
     * Get modern styles matching _404.php but with smaller fonts.
     */
    private function getStyles(): string
    {
        return <<<'CSS'
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap');

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    min-height: 100vh;
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
    padding: 40px 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.error-container {
    text-align: left;
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    max-width: 900px;
    width: 100%;
    overflow: hidden;
    animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

.error-header {
    background: linear-gradient(135deg, #e94560, #ff6b6b);
    padding: 20px 24px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.error-icon {
    font-size: 24px;
    color: #fff;
}

.error-header h2 {
    color: #fff;
    font-size: 20px;
    font-weight: 600;
    margin: 0;
}

.error-body {
    padding: 24px;
}

.error-message {
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    padding: 16px;
    font-family: 'JetBrains Mono', monospace;
    font-size: 14px;
    color: #fff;
    white-space: pre-wrap;
    word-break: break-all;
    margin-bottom: 16px;
}

.error-location {
    display: flex;
    align-items: center;
    gap: 8px;
    font-family: 'JetBrains Mono', monospace;
    font-size: 13px;
    color: rgba(255, 255, 255, 0.6);
    margin-bottom: 16px;
}

.location-file {
    color: #a5b4fc;
}

.location-line {
    color: #f87171;
}

.error-code {
    font-family: 'JetBrains Mono', monospace;
    background: rgba(255, 255, 255, 0.1);
    padding: 8px 12px;
    border-radius: 6px;
    color: rgba(255, 255, 255, 0.5);
    font-size: 12px;
    display: inline-block;
}

.debug-section {
    margin-top: 20px;
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.btn-copy {
    background: linear-gradient(135deg, #e94560, #ff6b6b);
    color: #fff;
    border: none;
    padding: 10px 20px;
    font-size: 14px;
    font-weight: 500;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-copy:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(233, 69, 96, 0.4);
}

.btn-outline {
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
    border: 1px solid rgba(255, 255, 255, 0.2);
    padding: 10px 20px;
    font-size: 14px;
    font-weight: 500;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-outline:hover {
    background: rgba(255, 255, 255, 0.15);
    border-color: rgba(255, 255, 255, 0.3);
}

.btn-success {
    background: #22c55e !important;
}

.trace-container, .previous-container {
    width: 100%;
    margin-top: 16px;
    text-align: left;
}

.trace-header, .previous-header {
    color: rgba(255, 255, 255, 0.6);
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 12px;
}

.trace-content, .previous-container {
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    padding: 12px;
    max-height: 400px;
    overflow-y: auto;
    font-family: 'JetBrains Mono', monospace;
    font-size: 12px;
}

.previous-container {
    margin-top: 12px;
}

.previous-item {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 6px;
    padding: 10px;
    margin-bottom: 8px;
}

.previous-item:last-child {
    margin-bottom: 0;
}

.previous-class {
    color: #f87171;
    font-weight: 600;
    margin-bottom: 4px;
}

.previous-message {
    color: rgba(255, 255, 255, 0.8);
    margin-bottom: 4px;
}

.previous-location {
    color: rgba(255, 255, 255, 0.5);
    font-size: 11px;
}

.trace-line {
    padding: 6px 0;
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    align-items: baseline;
    color: rgba(255, 255, 255, 0.7);
}

.trace-num {
    color: #6b7280;
    min-width: 24px;
}

.trace-func {
    color: #60a5fa;
}

.trace-args {
    color: #9ca3af;
    font-size: 11px;
}

.trace-file {
    color: #a5b4fc;
    margin-left: auto;
}

@media (max-width: 600px) {
    .error-container {
        border-radius: 12px;
    }
    .error-header {
        padding: 16px 20px;
    }
    .error-body {
        padding: 16px;
    }
}
CSS;
    }
}
