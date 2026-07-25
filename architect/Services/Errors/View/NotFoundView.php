<?php

declare(strict_types=1);

namespace Architect\Services\Errors\View;

/**
 * 404 Not Found view - matches _404.php style.
 */
class NotFoundView extends ErrorPageView
{
    public function __construct(
        private readonly string $message = 'Page not found'
    ) {}

    public function render(): void
    {
        $this->cleanOutputBuffer();

        echo <<<'HTML'
            <!DOCTYPE html>
            <html lang="ru">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>404 - Страница не найдена</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
                <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
                <style>
                    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
                    
                    * {
                        margin: 0;
                        padding: 0;
                        box-sizing: border-box;
                    }
                    
                    body {
                        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                        min-height: 100vh;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
                        padding: 20px;
                    }
                    
                    .error-container {
                        text-align: center;
                        padding: 60px 40px;
                        background: rgba(255, 255, 255, 0.05);
                        backdrop-filter: blur(10px);
                        border: 1px solid rgba(255, 255, 255, 0.1);
                        border-radius: 24px;
                        max-width: 500px;
                        width: 100%;
                        animation: fadeInUp 0.6s ease-out;
                    }
                    
                    @keyframes fadeInUp {
                        from {
                            opacity: 0;
                            transform: translateY(30px);
                        }
                        to {
                            opacity: 1;
                            transform: translateY(0);
                        }
                    }
                    
                    .error-icon {
                        font-size: 80px;
                        color: #e94560;
                        margin-bottom: 20px;
                        animation: pulse 2s infinite;
                    }
                    
                    @keyframes pulse {
                        0%, 100% { transform: scale(1); }
                        50% { transform: scale(1.05); }
                    }
                    
                    h1 {
                        font-size: 120px;
                        font-weight: 700;
                        color: #fff;
                        line-height: 1;
                        margin: 0;
                        background: linear-gradient(135deg, #e94560, #ff6b6b);
                        -webkit-background-clip: text;
                        -webkit-text-fill-color: transparent;
                        background-clip: text;
                    }
                    
                    h2 {
                        color: #fff;
                        margin: 20px 0;
                        font-weight: 600;
                        font-size: 28px;
                    }
                    
                    p {
                        color: rgba(255, 255, 255, 0.7);
                        margin: 0 0 30px;
                        font-size: 16px;
                        line-height: 1.6;
                    }
                    
                    .btn-home {
                        display: inline-flex;
                        align-items: center;
                        gap: 10px;
                        background: linear-gradient(135deg, #e94560, #ff6b6b);
                        color: #fff;
                        border: none;
                        padding: 14px 32px;
                        font-size: 16px;
                        font-weight: 500;
                        border-radius: 12px;
                        text-decoration: none;
                        transition: all 0.3s ease;
                    }
                    
                    .btn-home:hover {
                        transform: translateY(-3px);
                        box-shadow: 0 10px 30px rgba(233, 69, 96, 0.4);
                        color: #fff;
                        text-decoration: none;
                    }
                    
                    .error-code {
                        font-family: 'Courier New', monospace;
                        background: rgba(255, 255, 255, 0.1);
                        padding: 8px 16px;
                        border-radius: 8px;
                        color: rgba(255, 255, 255, 0.5);
                        font-size: 14px;
                        margin-top: 20px;
                    }
                </style>
            </head>
            <body>
                <div class="error-container">
                    <div class="error-icon">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <h1>404</h1>
                    <h2>Страница не найдена</h2>
                    <p>{$this->escape($this->message)}</p>
                    <a href="/" class="btn-home">
                        <i class="bi bi-house-door-fill"></i>
                        Вернуться на главную
                    </a>
                    <div class="error-code">ERROR 404</div>
                </div>
            </body>
            </html>
            HTML;
    }
}
