<?php

declare(strict_types=1);

namespace Architect\Services\Performance\Export;

use Architect\Services\Performance\Contracts\PerformanceMonitorInterface;
use Architect\Services\Performance\Export\Formats\CsvExporter;
use Architect\Services\Performance\Export\Formats\JsonExporter;

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
        try {
            $result = $this->export($format, $options);
            return $result->saveToFile($filepath);
        } catch (\Exception $e) {
            error_log('Export failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getSupportedFormats(): array
    {
        return array_keys($this->exporters);
    }

    public function getExporter(string $format): ?ExporterInterface
    {
        return $this->exporters[$format] ?? null;
    }

    public function sendExport(string $format, array $options = []): void
    {
        try {
            $result = $this->export($format, $options);
            $result->output();
        } catch (\Exception $e) {
            http_response_code(500);
            echo 'Export failed: ' . $e->getMessage();
        }
    }
}
