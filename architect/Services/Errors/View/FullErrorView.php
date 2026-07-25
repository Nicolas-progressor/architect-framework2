<?php

declare(strict_types=1);

namespace Architect\Services\Errors\View;

/**
 * Full error view with context information:
 * - Stack trace
 * - Request data (headers, body, params)
 * - Route info
 * - Database queries
 * - System info (PHP, framework version)
 */
class FullErrorView extends ErrorPageView
{
    private array $data;
    private ?\Throwable $exception;

    public function __construct(
        array $data,
        ?\Throwable $exception = null
    ) {
        $this->data = $data;
        $this->exception = $exception;
    }

    public function render(): void
    {
        $this->cleanOutputBuffer();

        $copyData = $this->buildCopyData();

        echo <<<HTML
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$this->escape($this->data['status'])} - Architect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        {$this->getStyles()}
    </style>
</head>
<body>
    <div class="error-page">
        <!-- Header with type and copy button -->
        <div class="error-header">
            <div class="error-type">
                <i class="bi bi-exclamation-octagon-fill"></i>
                <span>{$this->escape($this->data['status'])}</span>
            </div>
            <button class="btn-copy" onclick="copyError()">
                <i class="bi bi-clipboard"></i> Copy to clipboard
            </button>
        </div>

        <div class="error-container">
            <!-- Title / Exception class -->
            <div class="error-title">
                {$this->escape($this->data['type'])}
            </div>

            <!-- Message -->
            <div class="error-message">
                {$this->escape($this->data['message'])}
            </div>

            <!-- File location -->
            <div class="error-location">
                <i class="bi bi-file-earmark-code"></i>
                <span class="file-path">{$this->escape($this->data['file'])}</span>
                <span class="file-line">:{$this->data['line']}</span>
            </div>

            <!-- Stack Trace -->
            {$this->renderTrace()}

            <!-- Request Info -->
            {$this->renderRequestInfo()}

            <!-- Route Info -->
            {$this->renderRouteInfo()}

            <!-- Queries -->
            {$this->renderQueries()}

            <!-- Meta info -->
            <div class="error-meta">
                <div class="meta-item">
                    <span class="meta-label">Status:</span>
                    <span class="meta-value">{$this->data['code']}</span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">PHP:</span>
                    <span class="meta-value">{$this->data['php_version']}</span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Framework:</span>
                    <span class="meta-value">{$this->data['framework_version']}</span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Error ID:</span>
                    <span class="meta-value code">{$this->data['error_id']}</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        const errorData = {$copyData};
        
        function copyError() {
            const text = JSON.stringify(errorData, null, 2);
            navigator.clipboard.writeText(text).then(() => {
                const btn = document.querySelector('.btn-copy');
                btn.innerHTML = '<i class="bi bi-check-lg"></i> Copied!';
                btn.classList.add('copied');
                setTimeout(() => {
                    btn.innerHTML = '<i class="bi bi-clipboard"></i> Copy to clipboard';
                    btn.classList.remove('copied');
                }, 2000);
            });
        }

        function toggleSection(id) {
            const el = document.getElementById(id);
            const btn = document.getElementById('btn-' + id);
            if (el.style.display === 'none') {
                el.style.display = 'block';
                btn.innerHTML = '<i class="bi bi-chevron-up"></i> Hide';
            } else {
                el.style.display = 'none';
                btn.innerHTML = '<i class="bi bi-chevron-down"></i> Show';
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
        return json_encode($this->data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Render stack trace section.
     */
    private function renderTrace(): string
    {
        if (empty($this->data['trace'])) {
            return '';
        }

        $traceHtml = '';
        $trace = $this->data['trace_array'] ?? [];
        
        foreach ($trace as $i => $item) {
            $file = $item['file'] ?? 'unknown';
            $lineNum = $item['line'] ?? 0;
            $function = $item['function'] ?? 'unknown';
            $class = $item['class'] ?? '';
            $type = $item['type'] ?? '';
            
            $traceHtml .= <<<HTML
<div class="trace-line">
    <span class="trace-num">#{$i}</span>
    <span class="trace-call">{$this->escape($class . $type . $function)}()</span>
    <span class="trace-file">{$this->escape($file)}</span>
    <span class="trace-line-num">:{$lineNum}</span>
</div>
HTML;
        }

        return <<<HTML
<div class="section">
    <button class="section-toggle" id="btn-trace" onclick="toggleSection('trace')">
        <i class="bi bi-chevron-down"></i> Stack Trace
    </button>
    <div id="trace" class="section-content">
        <div class="trace-container">
            {$traceHtml}
        </div>
    </div>
</div>
HTML;
    }

    /**
     * Render request info section.
     */
    private function renderRequestInfo(): string
    {
        if (empty($this->data['request'])) {
            return '';
        }

        $req = $this->data['request'];
        $method = $req['method'] ?? 'CLI';
        $url = $req['url'] ?? '';

        // Headers
        $headersHtml = '';
        foreach ($req['headers'] ?? [] as $name => $value) {
            $headersHtml .= <<<HTML
<div class="header-item">
    <span class="header-name">{$this->escape($name)}:</span>
    <span class="header-value">{$this->escape($value)}</span>
</div>
HTML;
        }

        // Request body
        $bodyHtml = '';
        $body = $req['body'] ?? null;
        if ($body !== null) {
            $bodyStr = is_array($body) ? json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $body;
            $bodyHtml = <<<HTML
<div class="subsection">
    <div class="subsection-title">Request Body</div>
    <pre class="code-block">{$this->escape($bodyStr)}</pre>
</div>
HTML;
        }

        // GET/POST params
        $paramsHtml = '';
        if (!empty($req['get'])) {
            $paramsHtml .= <<<HTML
<div class="subsection">
    <div class="subsection-title">GET Parameters</div>
    <pre class="code-block">{$this->escape(json_encode($req['get'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))}</pre>
</div>
HTML;
        }
        if (!empty($req['post'])) {
            $paramsHtml .= <<<HTML
<div class="subsection">
    <div class="subsection-title">POST Parameters</div>
    <pre class="code-block">{$this->escape(json_encode($req['post'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))}</pre>
</div>
HTML;
        }

        return <<<HTML
<div class="section">
    <button class="section-toggle" id="btn-request" onclick="toggleSection('request')">
        <i class="bi bi-chevron-down"></i> Request
    </button>
    <div id="request" class="section-content">
        <div class="request-line">
            <span class="method method-{$this->escape(strtolower($method))}">{$method}</span>
            <span class="url">{$this->escape($url)}</span>
        </div>
        
        <div class="subsection">
            <div class="subsection-title">Headers</div>
            <div class="headers-list">
                {$headersHtml}
            </div>
        </div>
        
        {$bodyHtml}
        {$paramsHtml}
    </div>
</div>
HTML;
    }

    /**
     * Render route info section.
     */
    private function renderRouteInfo(): string
    {
        if (empty($this->data['route'])) {
            return '';
        }

        $route = $this->data['route'];
        
        if (isset($route['error'])) {
            return '';
        }

        $module = $route['module'] ?? '-';
        $controller = $route['controller'] ?? '-';
        $action = $route['action'] ?? '-';
        $segments = implode(' / ', $route['segments'] ?? []);

        return <<<HTML
<div class="section">
    <button class="section-toggle" id="btn-route" onclick="toggleSection('route')">
        <i class="bi bi-chevron-down"></i> Route
    </button>
    <div id="route" class="section-content">
        <div class="route-info">
            <div class="route-item">
                <span class="route-label">Module:</span>
                <span class="route-value">{$this->escape($module)}</span>
            </div>
            <div class="route-item">
                <span class="route-label">Controller:</span>
                <span class="route-value">{$this->escape($controller)}</span>
            </div>
            <div class="route-item">
                <span class="route-label">Action:</span>
                <span class="route-value">{$this->escape($action)}</span>
            </div>
            <div class="route-item">
                <span class="route-label">URL Segments:</span>
                <span class="route-value">{$this->escape($segments)}</span>
            </div>
        </div>
    </div>
</div>
HTML;
    }

    /**
     * Render database queries section.
     */
    private function renderQueries(): string
    {
        if (empty($this->data['queries'])) {
            return '';
        }

        $queries = $this->data['queries'];
        $queriesHtml = '';

        foreach ($queries as $i => $query) {
            $sql = $query['query'] ?? '';
            $duration = $query['duration'] ?? 0;
            $params = $query['params'] ?? [];
            $isSlow = ($query['is_slow'] ?? false) ? ' slow' : '';
            
            $paramsStr = !empty($params) ? json_encode($params, JSON_UNESCAPED_UNICODE) : '';
            $durationMs = round($duration * 1000, 2);
            
            $queriesHtml .= <<<HTML
<div class="query-item{$isSlow}">
    <div class="query-header">
        <span class="query-num">#{$i}</span>
        <span class="query-duration">{$durationMs} ms</span>
    </div>
    <pre class="query-sql">{$this->escape($sql)}</pre>
    {$this->renderQueryParams($paramsStr)}
</div>
HTML;
        }

        $queryCount = $this->data['query_count'] ?? count($queries);
        
        return <<<HTML
<div class="section">
    <button class="section-toggle" id="btn-queries" onclick="toggleSection('queries')">
        <i class="bi bi-chevron-down"></i> Database Queries ({$queryCount})
    </button>
    <div id="queries" class="section-content">
        <div class="queries-list">
            {$queriesHtml}
        </div>
    </div>
</div>
HTML;
    }

    /**
     * Render query parameters.
     */
    private function renderQueryParams(string $paramsStr): string
    {
        if (empty($paramsStr)) {
            return '';
        }

        return <<<HTML
<div class="query-params">
    <span class="params-label">Parameters:</span>
    <pre class="params-json">{$this->escape($paramsStr)}</pre>
</div>
HTML;
    }

    /**
     * Get styles for full error page.
     */
    private function getStyles(): string
    {
        return <<<'CSS'
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap');

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
    color: #e5e5e5;
    font-size: 14px;
    line-height: 1.5;
    min-height: 100vh;
}

.error-page {
    min-height: 100vh;
}

/* Header */
.error-header {
    background: linear-gradient(135deg, #e94560, #ff6b6b);
    padding: 16px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.error-type {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #fff;
    font-size: 18px;
    font-weight: 600;
}

.error-type i {
    font-size: 24px;
}

.btn-copy {
    background: rgba(255,255,255,0.2);
    border: 1px solid rgba(255,255,255,0.3);
    color: #fff;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
}

.btn-copy:hover {
    background: rgba(255,255,255,0.3);
}

.btn-copy.copied {
    background: #22c55e;
    border-color: #22c55e;
}

/* Container */
.error-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 24px;
}

/* Title */
.error-title {
    font-size: 16px;
    font-weight: 600;
    color: #f87171;
    margin-bottom: 12px;
    font-family: 'JetBrains Mono', monospace;
}

/* Message */
.error-message {
    background: #1a1a1a;
    border: 1px solid #333;
    border-radius: 8px;
    padding: 16px;
    font-family: 'JetBrains Mono', monospace;
    font-size: 14px;
    color: #e5e5e5;
    white-space: pre-wrap;
    word-break: break-word;
    margin-bottom: 16px;
}

/* Location */
.error-location {
    display: flex;
    align-items: center;
    gap: 8px;
    font-family: 'JetBrains Mono', monospace;
    font-size: 13px;
    color: #9ca3af;
    margin-bottom: 24px;
}

.file-path {
    color: #a5b4fc;
}

.file-line {
    color: #f87171;
}

/* Sections */
.section {
    margin-bottom: 16px;
    border: 1px solid #333;
    border-radius: 8px;
    overflow: hidden;
}

.section-toggle {
    width: 100%;
    background: #1a1a1a;
    border: none;
    color: #e5e5e5;
    padding: 12px 16px;
    text-align: left;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: background 0.2s;
}

.section-toggle:hover {
    background: #262626;
}

.section-content {
    background: #0f0f0f;
    border-top: 1px solid #333;
    padding: 16px;
    display: none;
}

/* Stack Trace */
.trace-container {
    font-family: 'JetBrains Mono', monospace;
    font-size: 12px;
    max-height: 400px;
    overflow-y: auto;
}

.trace-line {
    display: flex;
    gap: 12px;
    padding: 4px 0;
    border-bottom: 1px solid #262626;
}

.trace-line:last-child {
    border-bottom: none;
}

.trace-num {
    color: #6b7280;
    min-width: 30px;
}

.trace-call {
    color: #60a5fa;
}

.trace-file {
    color: #a5b4fc;
    flex: 1;
}

.trace-line-num {
    color: #f87171;
}

/* Request */
.request-line {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
    font-family: 'JetBrains Mono', monospace;
    font-size: 13px;
}

.method {
    padding: 4px 10px;
    border-radius: 4px;
    font-weight: 600;
    font-size: 12px;
}

.method-get { background: #22c55e; color: #fff; }
.method-post { background: #3b82f6; color: #fff; }
.method-put { background: #f59e0b; color: #fff; }
.method-patch { background: #8b5cf6; color: #fff; }
.method-delete { background: #ef4444; color: #fff; }
.method-cli { background: #6b7280; color: #fff; }

.url {
    color: #e5e5e5;
}

.subsection {
    margin-bottom: 16px;
}

.subsection:last-child {
    margin-bottom: 0;
}

.subsection-title {
    font-size: 12px;
    font-weight: 600;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.headers-list {
    background: #1a1a1a;
    border-radius: 6px;
    padding: 12px;
}

.header-item {
    display: flex;
    gap: 8px;
    padding: 4px 0;
    font-family: 'JetBrains Mono', monospace;
    font-size: 12px;
}

.header-name {
    color: #60a5fa;
}

.header-value {
    color: #e5e5e5;
    word-break: break-all;
}

.code-block {
    background: #1a1a1a;
    border-radius: 6px;
    padding: 12px;
    font-family: 'JetBrains Mono', monospace;
    font-size: 12px;
    color: #e5e5e5;
    overflow-x: auto;
    white-space: pre-wrap;
    word-break: break-all;
}

/* Route */
.route-info {
    display: grid;
    grid-template-columns: 120px 1fr;
    gap: 8px;
}

.route-item {
    display: contents;
}

.route-label {
    color: #9ca3af;
    font-size: 13px;
}

.route-value {
    color: #e5e5e5;
    font-family: 'JetBrains Mono', monospace;
    font-size: 13px;
}

/* Queries */
.queries-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.query-item {
    background: #1a1a1a;
    border-radius: 6px;
    padding: 12px;
}

.query-item.slow {
    border-left: 3px solid #f59e0b;
}

.query-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
}

.query-num {
    color: #6b7280;
    font-size: 12px;
}

.query-duration {
    color: #f59e0b;
    font-size: 12px;
    font-family: 'JetBrains Mono', monospace;
}

.query-sql {
    font-family: 'JetBrains Mono', monospace;
    font-size: 12px;
    color: #e5e5e5;
    white-space: pre-wrap;
    word-break: break-all;
    margin: 0;
}

.query-params {
    margin-top: 8px;
    padding-top: 8px;
    border-top: 1px solid #333;
}

.params-label {
    font-size: 11px;
    color: #9ca3af;
}

.params-json {
    font-family: 'JetBrains Mono', monospace;
    font-size: 11px;
    color: #a5b4fc;
    margin: 4px 0 0 0;
    white-space: pre-wrap;
}

/* Meta */
.error-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    padding: 16px;
    background: #1a1a1a;
    border-radius: 8px;
    margin-top: 24px;
}

.meta-item {
    display: flex;
    gap: 8px;
    font-size: 13px;
}

.meta-label {
    color: #9ca3af;
}

.meta-value {
    color: #e5e5e5;
}

.meta-value.code {
    font-family: 'JetBrains Mono', monospace;
    font-size: 12px;
}

/* Scrollbar */
::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

::-webkit-scrollbar-track {
    background: #1a1a1a;
}

::-webkit-scrollbar-thumb {
    background: #404040;
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: #525252;
}

/* Responsive */
@media (max-width: 768px) {
    .error-header {
        flex-direction: column;
        gap: 12px;
        text-align: center;
    }
    
    .error-container {
        padding: 16px;
    }
    
    .route-info {
        grid-template-columns: 1fr;
    }
    
    .error-meta {
        flex-direction: column;
    }
}
CSS;
    }
}
