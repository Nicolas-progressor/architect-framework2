<?php

declare(strict_types=1);

namespace Architect\Services\Errors\View;

/**
 * Error view - displays generic errors with Laravel-like styling.
 */
class ErrorView extends ErrorPageView
{
    private string $file = 'unknown';
    private int $line = 0;

    public function __construct(
        private readonly string $message,
        private readonly bool $debug = false,
        private readonly ?string $trace = null
    ) {
        $this->parseTraceForLocation();
    }

    /**
     * Parse trace to extract file and line information.
     */
    private function parseTraceForLocation(): void
    {
        if ($this->trace === null) {
            return;
        }

        $lines = explode("\n", $this->trace);
        foreach ($lines as $line) {
            if (preg_match('/called at \[(.+?):(\d+)\]/', $line, $matches)) {
                $this->file = $matches[1];
                $this->line = (int) $matches[2];
                break;
            }
        }
    }

    public function render(): void
    {
        $this->cleanOutputBuffer();

        $errorId = bin2hex(random_bytes(8));
        $copyData = $this->debug && $this->trace ? $this->buildCopyData() : '{}';

        echo <<<HTML
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ошибка — Architect</title>
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
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <h2>Ошибка</h2>
            </div>
            
            <div class="error-body">
                <div class="error-message">{$this->escape($this->message)}</div>
                
                <div class="error-location">
                    <i class="bi bi-file-code"></i>
                    <span class="location-file">{$this->escape($this->file)}</span>
                    <span class="location-line">:{$this->line}</span>
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
    </script>
</body>
</html>
HTML;
    }

    /**
     * Build data for copy to clipboard.
     */
    private function buildCopyData(): string
    {
        $data = [
            'message' => $this->message,
            'file' => $this->file,
            'line' => $this->line,
            'trace' => $this->trace ?? '',
            'error_id' => bin2hex(random_bytes(8))
        ];
        
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

        $traceHtml = '';
        
        if ($this->trace) {
            $traceLines = explode("\n", $this->trace);
            
            foreach ($traceLines as $line) {
                if (trim($line) === '') continue;
                
                // Parse trace line to extract file, line, function
                if (preg_match('/#(\d+)\s+(.+?)\s+called at \[(.+?):(\d+)\]/', $line, $matches)) {
                    [, $num, $func, $file, $lineNum] = $matches;
                    $fileName = basename($file);
                    $traceHtml .= <<<HTML
<div class="trace-line">
    <span class="trace-num">#{$num}</span>
    <span class="trace-func">{$this->escape($func)}</span>
    <span class="trace-file">{$this->escape($fileName)}</span>
    <span class="trace-line-num">:{$lineNum}</span>
</div>
HTML;
                } else {
                    $traceHtml .= '<div class="trace-line">' . $this->escape($line) . '</div>';
                }
            }
        }

        if ($traceHtml === '') {
            return '';
        }

        return <<<HTML
<div class="debug-section">
    <button id="copyBtn" class="btn btn-copy" onclick="copyError()">
        <i class="bi bi-clipboard"></i> Копировать
    </button>
    
    <button id="toggleTraceBtn" class="btn btn-outline" onclick="toggleTrace()">
        <i class="bi bi-chevron-down"></i> Stack Trace
    </button>
    
    <div id="traceContent" class="trace-container" style="display: none;">
        <div class="trace-header">Stack Trace:</div>
        <div class="trace-content">{$traceHtml}</div>
    </div>
</div>
HTML;
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
    max-width: 800px;
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

.trace-container {
    width: 100%;
    margin-top: 16px;
    text-align: left;
}

.trace-header {
    color: rgba(255, 255, 255, 0.6);
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 12px;
}

.trace-content {
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    padding: 12px;
    max-height: 300px;
    overflow-y: auto;
    font-family: 'JetBrains Mono', monospace;
    font-size: 12px;
}

.trace-line {
    padding: 4px 0;
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    color: rgba(255, 255, 255, 0.7);
}

.trace-num {
    color: #6b7280;
    min-width: 24px;
}

.trace-func {
    color: #60a5fa;
}

.trace-file {
    color: #a5b4fc;
}

.trace-line-num {
    color: #f87171;
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
