# План экспорта метрик производительности

## Обзор
Система экспорта метрик производительности в различные форматы (JSON, CSV) для дальнейшего анализа, отчетности и интеграции с внешними системами.

## Форматы экспорта

### 1. JSON Export
- **Полный формат**: Все метрики со всеми деталями
- **Упрощенный формат**: Только ключевые метрики
- **Структурированный формат**: Иерархическая структура для удобства анализа

### 2. CSV Export
- **Табличный формат**: Данные в виде таблицы для импорта в Excel/Google Sheets
- **Многолистовой формат**: Разные листы для разных типов метрик
- **Временные ряды**: Данные с временными метками для анализа трендов

### 3. Другие форматы
- **HTML Report**: Интерактивный HTML отчет
- **PDF Report**: Форматированный PDF документ
- **Markdown**: Отчет в формате Markdown для документации

## Архитектура системы экспорта

### 1. Экспортный менеджер

```php
<?php

declare(strict_types=1);

namespace Architect\Services\Performance\Export;

use Architect\Services\Performance\Contracts\PerformanceMonitorInterface;
use Architect\Services\Performance\Export\Formats\JsonExporter;
use Architect\Services\Performance\Export\Formats\CsvExporter;
use Architect\Services\Performance\Export\Formats\HtmlExporter;
use Architect\Services\Performance\Export\Formats\PdfExporter;

class ExportManager
{
    private PerformanceMonitorInterface $monitor;
    private array $exporters = [];
    private array $config = [];
    
    public function __construct(PerformanceMonitorInterface $monitor, array $config = [])
    {
        $this->monitor = $monitor;
        $this->config = $config;
        
        $this->initializeExporters();
    }
    
    private function initializeExporters(): void
    {
        $this->exporters = [
            'json' => new JsonExporter(),
            'csv' => new CsvExporter(),
            'html' => new HtmlExporter(),
            'pdf' => new PdfExporter(),
            'markdown' => new MarkdownExporter(),
        ];
    }
    
    public function export(string $format, array $options = []): ExportResult
    {
        if (!isset($this->exporters[$format])) {
            throw new \InvalidArgumentException("Unsupported export format: {$format}");
        }
        
        $exporter = $this->exporters[$format];
        $metrics = $this->monitor->collectMetrics();
        
        return $exporter->export($metrics, $options);
    }
    
    public function exportToFile(string $format, string $filepath, array $options = []): bool
    {
        $result = $this->export($format, $options);
        
        return file_put_contents($filepath, $result->getContent()) !== false;
    }
    
    public function getSupportedFormats(): array
    {
        return array_keys($this->exporters);
    }
    
    public function getExporter(string $format): ?ExporterInterface
    {
        return $this->exporters[$format] ?? null;
    }
}
```

### 2. Интерфейс экспортера

```php
<?php

declare(strict_types=1);

namespace Architect\Services\Performance\Export\Contracts;

interface ExporterInterface
{
    public function export(array $metrics, array $options = []): ExportResult;
    
    public function getFormat(): string;
    
    public function getMimeType(): string;
    
    public function getFileExtension(): string;
}
```

### 3. Результат экспорта

```php
<?php

declare(strict_types=1);

namespace Architect\Services\Performance\Export;

class ExportResult
{
    private string $content;
    private string $format;
    private string $mimeType;
    private string $filename;
    private array $metadata;
    
    public function __construct(
        string $content,
        string $format,
        string $mimeType,
        string $filename,
        array $metadata = []
    ) {
        $this->content = $content;
        $this->format = $format;
        $this->mimeType = $mimeType;
        $this->filename = $filename;
        $this->metadata = $metadata;
    }
    
    public function getContent(): string
    {
        return $this->content;
    }
    
    public function getFormat(): string
    {
        return $this->format;
    }
    
    public function getMimeType(): string
    {
        return $this->mimeType;
    }
    
    public function getFilename(): string
    {
        return $this->filename;
    }
    
    public function getMetadata(): array
    {
        return $this->metadata;
    }
    
    public function sendHeaders(): void
    {
        header("Content-Type: {$this->mimeType}");
        header("Content-Disposition: attachment; filename=\"{$this->filename}\"");
        header("Content-Length: " . strlen($this->content));
    }
    
    public function output(): void
    {
        $this->sendHeaders();
        echo $this->content;
    }
    
    public function saveToFile(string $filepath): bool
    {
        return file_put_contents($filepath, $this->content) !== false;
    }
}
```

## Реализация экспортеров

### 1. JSON Exporter

```php
<?php

declare(strict_types=1);

namespace Architect\Services\Performance\Export\Formats;

use Architect\Services\Performance\Export\Contracts\ExporterInterface;
use Architect\Services\Performance\Export\ExportResult;

class JsonExporter implements ExporterInterface
{
    public function export(array $metrics, array $options = []): ExportResult
    {
        $options = array_merge([
            'pretty' => true,
            'include_timestamps' => true,
            'include_metadata' => true,
            'max_depth' => 10,
        ], $options);
        
        // Подготовка данных для экспорта
        $exportData = $this->prepareData($metrics, $options);
        
        // Кодирование в JSON
        $jsonOptions = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        
        if ($options['pretty']) {
            $jsonOptions |= JSON_PRETTY_PRINT;
        }
        
        $content = json_encode($exportData, $jsonOptions, $options['max_depth']);
        
        if ($content === false) {
            throw new \RuntimeException('Failed to encode metrics to JSON');
        }
        
        // Генерация имени файла
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "performance_metrics_{$timestamp}.json";
        
        return new ExportResult(
            $content,
            'json',
            'application/json',
            $filename,
            [
                'timestamp' => time(),
                'metrics_count' => count($metrics),
                'file_size' => strlen($content),
            ]
        );
    }
    
    private function prepareData(array $metrics, array $options): array
    {
        $data = [
            'export' => [
                'format' => 'json',
                'version' => '1.0',
                'generated_at' => date('c'),
                'generator' => 'Architect Framework Performance Monitor',
            ],
            'metrics' => $metrics,
        ];
        
        if ($options['include_metadata']) {
            $data['metadata'] = [
                'php_version' => PHP_VERSION,
                'architect_version' => $this->getArchitectVersion(),
                'environment' => $this->getEnvironment(),
                'hostname' => gethostname(),
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            ];
        }
        
        if ($options['include_timestamps']) {
            $data['timestamps'] = [
                'collection_start' => $metrics['collection_start'] ?? null,
                'collection_end' => $metrics['collection_end'] ?? null,
                'export_time' => microtime(true),
            ];
        }
        
        return $data;
    }
    
    public function getFormat(): string
    {
        return 'json';
    }
    
    public function getMimeType(): string
    {
        return 'application/json';
    }
    
    public function getFileExtension(): string
    {
        return 'json';
    }
    
    private function getArchitectVersion(): string
    {
        // Получение версии Architect Framework
        return '2.0.0'; // Заглушка
    }
    
    private function getEnvironment(): string
    {
        return $_SERVER['APP_ENV'] ?? 'production';
    }
}
```

### 2. CSV Exporter

```php
<?php

declare(strict_types=1);

namespace Architect\Services\Performance\Export\Formats;

use Architect\Services\Performance\Export\Contracts\ExporterInterface;
use Architect\Services\Performance\Export\ExportResult;

class CsvExporter implements ExporterInterface
{
    public function export(array $metrics, array $options = []): ExportResult
    {
        $options = array_merge([
            'delimiter' => ',',
            'enclosure' => '"',
            'escape' => '\\',
            'include_headers' => true,
            'flatten_arrays' => true,
            'multiple_sheets' => false,
        ], $options);
        
        if ($options['multiple_sheets']) {
            $content = $this->exportMultipleSheets($metrics, $options);
            $filename = "performance_metrics_" . date('Y-m-d_H-i-s') . ".zip";
            $mimeType = 'application/zip';
        } else {
            $content = $this->exportSingleSheet($metrics, $options);
            $filename = "performance_metrics_" . date('Y-m-d_H-i-s') . ".csv";
            $mimeType = 'text/csv';
        }
        
        return new ExportResult(
            $content,
            'csv',
            $mimeType,
            $filename,
            [
                'timestamp' => time(),
                'row_count' => $this->countRows($metrics),
                'file_size' => strlen($content),
            ]
        );
    }
    
    private function exportSingleSheet(array $metrics, array $options): string
    {
        $rows = [];
        
        // Заголовки
        if ($options['include_headers']) {
            $headers = $this->extractHeaders($metrics);
            $rows[] = $this->formatCsvRow($headers, $options);
        }
        
        // Данные
        $dataRows = $this->flattenMetrics($metrics, $options['flatten_arrays']);
        foreach ($dataRows as $row) {
            $rows[] = $this->formatCsvRow($row, $options);
        }
        
        return implode("\n", $rows);
    }
    
    private function exportMultipleSheets(array $metrics, array $options): string
    {
        // Создание ZIP архива с несколькими CSV файлами
        $zip = new \ZipArchive();
        $zipFilename = tempnam(sys_get_temp_dir(), 'perf_export_') . '.zip';
        
        if ($zip->open($zipFilename, \ZipArchive::CREATE) !== true) {
            throw new \RuntimeException('Failed to create ZIP archive');
        }
        
        // Экспорт разных типов метрик в отдельные файлы
        $sheets = [
            'response_time' => $metrics['response_time'] ?? [],
            'memory_usage' => $metrics['memory_usage'] ?? [],
            'database_queries' => $metrics['database_queries'] ?? [],
            'cache_efficiency' => $metrics['cache_efficiency'] ?? [],
            'template_rendering' => $metrics['template_rendering'] ?? [],
            'service_loading' => $metrics['service_loading'] ?? [],
            'analysis' => $metrics['analysis'] ?? [],
            'recommendations' => $metrics['recommendations'] ?? [],
        ];
        
        foreach ($sheets as $sheetName => $sheetData) {
            if (!empty($sheetData)) {
                $csvContent = $this->exportSingleSheet([$sheetName => $sheetData], $options);
                $zip->addFromString("{$sheetName}.csv", $csvContent);
            }
        }
        
        // Добавление summary файла
        $summary = $this->createSummarySheet($metrics);
        $zip->addFromString('summary.csv', $summary);
        
        $zip->close();
        
        $content = file_get_contents($zipFilename);
        unlink($zipFilename);
        
        return $content;
    }
    
    private function extractHeaders(array $metrics): array
    {
        $headers = [];
        
        foreach ($metrics as $category => $data) {
            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    if (is_scalar($value)) {
                        $headers[] = "{$category}.{$key}";
                    }
                }
            } else {
                $headers[] = $category;
            }
        }
        
        return $headers;
    }
    
    private function flattenMetrics(array $metrics, bool $flattenArrays = true): array
    {
        $rows = [];
        
        foreach ($metrics as $category => $data) {
            if (is_array($data) && $flattenArrays) {
                $row = [];
                foreach ($data as $key => $value) {
                    if (is_scalar($value)) {
                        $row["{$category}.{$key}"] = $value;
                    } elseif (is_array($value)) {
                        // Рекурсивное flatten для вложенных массивов
                        $nested = $this->flattenNestedArray($value, "{$category}.{$key}");
                        $row = array_merge($row, $nested);
                    }
                }
                $rows[] = $row;
            } else {
                $rows[] = [$category => $data];
            }
        }
        
        return $rows;
    }
    
    private function flattenNestedArray(array $array, string $prefix): array
    {
        $result = [];
        
        foreach ($array as $key => $value) {
            $fullKey = "{$prefix}.{$key}";
            
            if (is_scalar($value)) {
                $result[$fullKey] = $value;
            } elseif (is_array($value)) {
                $nested = $this->flattenNestedArray($value, $fullKey);
                $result = array_merge($result, $nested);
            }
        }
        
        return $result;
    }
    
    private function formatCsvRow(array $row, array $options): string
    {
        $delimiter = $options['delimiter'];
        $enclosure = $options['enclosure'];
        $escape = $options['escape'];
        
        $formatted = [];
        
        foreach ($row as $value) {
            if ($value === null) {
                $formatted[] = '';
            } elseif (is_bool($value)) {
                $formatted[] = $value ? 'true' : 'false';
            } elseif (is_numeric($value)) {
                $formatted[] = $value;
            } else {
                // Экранирование специальных символов
                $value = (string) $value;
                if (strpos($value, $delimiter) !== false || 
                    strpos($value, $enclosure) !== false || 
                    strpos($value, "\n") !== false || 
                    strpos($value, "\r") !== false) {
                    $value = $enclosure . str_replace($enclosure, $escape . $enclosure, $value) . $enclosure;
                }
                $formatted[] = $value;
            }
        }
        
        return implode($delimiter, $formatted);
    }
    
    private function createSummarySheet(array $metrics): string
    {
        $summary = [
            ['Metric Category', 'Count', 'Status', 'Value'],
            ['Response Time', $metrics['response_time']['current'] ?? 0, $this->getStatus($metrics['response_time']), 'ms'],
            ['Memory Usage', $metrics['memory_usage']['peak_mb'] ?? 0, $this->getStatus($metrics['memory_usage']), 'MB'],
            ['Database Queries', $metrics['database_queries']['count'] ?? 0, $this->getStatus($metrics['database_queries']), 'queries'],
            ['Cache Hit Ratio', $metrics['cache_efficiency']['hit_ratio'] ?? 0, $this->getStatus($metrics['cache_efficiency']), '%'],
            ['Performance Score', $metrics['analysis']['performance_score'] ?? 0, $this->getScoreStatus($metrics['analysis']['performance_score'] ?? 0), 'points'],
        ];
        
        $rows = [];
        foreach ($summary as $row) {
            $rows[] = implode(',', array_map(function($cell) {
                return '"' . str_replace('"', '""', $cell) . '"';
            }, $row));
        }
        
        return implode("\n", $rows);
    }
    
    private function getStatus(array $metric): string
    {
        if (isset($metric['threshold_exceeded']) && $metric['threshold_exceeded']) {
            return 'CRITICAL';
        }
        
        return 'OK';
    }
    
    private function getScoreStatus(int $score): string
    {
        if ($score >= 90) return 'EXCELLENT';
        if ($score >= 70) return 'GOOD';
        if ($score >= 50) return 'FAIR';
        return 'POOR';
    }
    
    private function countRows(array $metrics): int
    {
        $count = 0;
        
        foreach ($metrics as $category => $data) {
            if (is_array($data)) {
                $count += count($data);
            } else {
                $count++;
            }
        }
        
        return $count;
    }
    
    public function getFormat(): string
    {
        return 'csv';
    }
    
    public function getMimeType(): string
    {
        return 'text/csv';
    }
    
    public function getFileExtension(): string
    {
        return 'csv';
    }
}
```

## Интеграция с Debug системой

### 1. Добавление кнопок экспорта в PerformanceTab

```javascript
// В PerformanceTab.php
function renderExportButtons() {
    let html = '<div class="performance-export-buttons">';
    html += '<button class="export-btn" data-format="json" title="Export as JSON">JSON</button>';
    html += '<button class="export-btn" data-format="csv" title="Export as CSV">CSV</button>';
    html += '<button class="export-btn" data-format="html" title="Export as HTML Report">HTML</button>';
    html += '<button class="export-btn" data-format="pdf" title="Export as PDF">PDF</button>';
    html += '</div>';
    
    return html;
}

// Добавление обработчиков событий
function setupExportHandlers() {
    document.querySelectorAll('.export-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const format = this.dataset.format;
            exportMetrics(format);
        });
    });
}

function exportMetrics(format) {
    // Показать индикатор загрузки
    showLoadingIndicator('Exporting...');
    
    // Создание формы для отправки запроса
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/debug/export-performance';
    form.style.display = 'none';
    
    const formatInput = document.createElement('input');
    formatInput.type = 'hidden';
    formatInput.name = 'format';
    formatInput.value = format;
    
    const metricsInput = document.createElement('input');
    metricsInput.type = 'hidden';
    metricsInput.name = 'metrics';
    metricsInput.value = JSON.stringify(debugData.performance_metrics);
    
    form.appendChild(formatInput);
    form.appendChild(metricsInput);
    document.body.appendChild(form);
    
    // Отправка формы
    form.submit();
    
    // Удаление формы
    setTimeout(() => {
        document.body.removeChild(form);
        hideLoadingIndicator();
    }, 100);
}
```

### 2. Контроллер экспорта

```php
<?php

declare(strict_types=1);

namespace Architect\Services\Debug\Controllers;

use Architect\Core\Container;
use Architect\Services\Performance\Export\ExportManager;

class PerformanceExportController
{
    private Container $container;
    
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    
    public function export(): void
    {
        // Проверка авторизации и прав доступа
        if (!$this->isAuthorized()) {
            http_response_code(403);
            echo 'Access denied';
            return;
        }
        
        // Получение параметров
        $format = $_POST['format'] ?? $_GET['format'] ?? 'json';
        $metrics = isset($_POST['metrics']) ? json_decode($_POST['metrics'], true) : null;
        
        if ($metrics === null) {
            // Если метрики не переданы, собрать текущие
            $debug = $this->container->get('debug');
            $data = $debug->getData();
            $metrics = $data['performance_metrics'] ?? [];
        }
        
        try {
            // Создание экспортного менеджера
            $monitor = $this->container->get('performance.monitor');
            $exportManager = new ExportManager($monitor);
            
            // Экспорт метрик
            $result = $exportManager->export($format, [
                'metrics' => $metrics,
                'include_timestamps' => true,
                'include_metadata' => true,
            ]);
            
            // Отправка результата
            $result->output();
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo 'Export failed: ' . $e->getMessage();
        }
    }
    
    private function isAuthorized(): bool
    {
        // Проверка IP whitelist из конфигурации debug
        $debug = $this->container->get('debug');
        $config = $debug->getConfig();
        
        $whitelist = $config['ip_whitelist'] ?? [];
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
        
        if (!empty($whitelist) && !in_array($clientIp, $whitelist, true)) {
            return false;
        }
        
        return true;
    }
}
```

### 3. Маршрут для экспорта

```json
// app/routes/debug.json
{
    "routes": {
        "/debug/export-performance": {
            "controller": "Architect\\Services\\Debug\\Controllers\\PerformanceExportController",
            "action": "export",
            "method": ["GET", "POST"]
        }
    }
}
```

## Опции экспорта

### 1. Конфигурация через debug.json

```json
{
    "enabled": true,
    "performance_monitoring": true,
    "performance_export": {
        "enabled": true,
        "formats": ["json", "csv", "html"],
        "default_format": "json",
        "options": {
            "json": {
                "pretty": true,
                "include_timestamps": true
            },
            "csv": {
                "delimiter": ",",
                "include_headers": true,
                "multiple_sheets": false
            },
            "html": {
                "template": "default",
                "include_charts": true
            }
        },
        "storage": {
            "enabled": false,
            "directory": "/var/log/architect/performance",
            "retention_days": 30
        }
    }
}
```

### 2. Программные опции

```php
$options = [
    // Общие опции
    'filename' => 'custom_filename',
    'timestamp_format' => 'Y-m-d_H-i-s',
    
    // Опции JSON
    'json_pretty' => true,
    'json_max_depth' => 10,
    
    // Опции CSV
    'csv_delimiter' => ',',
    'csv_include_headers' => true,
    'csv_multiple_sheets' => true,
    
    // Опции фильтрации
    'filter_categories' => ['response_time', 'memory_usage'],
    'exclude_categories' => ['system_metrics'],
    'time_range' => [
        'start' => '2024-01-01 00:00:00',
        'end' => '2024-01-31 23:59:59'
    ],
    
    // Опции форматирования
    'round_decimals' => 2,
    'human_readable' => true, // Конвертировать байты в KB/MB/GB
    'include_percentages' => true,
];
```

## Безопасность

### 1. Контроль доступа
- Проверка IP whitelist
- Проверка авторизации пользователя
- Ограничение частоты запросов

### 2. Валидация данных
- Проверка формата экспорта
- Валидация метрик
- Ограничение размера данных

### 3. Защита от атак
- Защита от XSS в экспортируемых данных
- Ограничение глубины рекурсии при flattening
- Экранирование специальных символов в CSV

## Производительность

### 1. Оптимизация памяти
- Потоковая обработка больших наборов данных
- Использование генераторов для итерации
- Очистка временных данных

### 2. Оптимизация скорости
- Кэширование подготовленных данных
- Параллельная обработка разных форматов
- Использование буферизации вывода

### 3. Масштабируемость
- Поддержка инкрементального экспорта
- Экспорт по частям (chunking)
- Фоновая обработка больших отчетов

## Тестирование

### 1. Unit тесты
- Тестирование форматов экспорта
- Тестирование обработки различных типов данных
- Тестирование edge cases

### 2. Интеграционные тесты
- Тестирование полного цикла экспорта
- Тестирование интеграции с Debug системой
- Тестирование производительности экспорта

### 3. End-to-end тесты
- Тестирование UI кнопок экспорта
- Тестирование скачивания файлов
- Тестирование различных браузеров

## Заключение
Система экспорта метрик производительности предоставит разработчикам гибкие возможности для анализа данных вне дебаг-меню, интеграции с внешними системами мониторинга и создания отчетов для дальнейшего анализа производительности Architect Framework 2.