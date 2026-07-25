<?php

namespace Architect\Services\Performance\Alerts;

use Architect\Services\Performance\Contracts\AlertManagerInterface;
use Architect\Services\Performance\Contracts\MetricStorageInterface;
use Architect\Services\Performance\Contracts\PerformanceMonitorInterface;

class AlertManager implements AlertManagerInterface
{
    private array $thresholds = [];
    private array $alerts = [];
    
    public function __construct(
        private PerformanceMonitorInterface $monitor,
        private MetricStorageInterface $storage
    ) {
        $this->loadDefaultThresholds();
    }
    
    /**
     * Load default threshold values for common metrics
     */
    private function loadDefaultThresholds(): void
    {
        $this->thresholds = [
            'response_time' => 2000, // 2 seconds
            'memory_usage' => 128 * 1024 * 1024, // 128MB
            'database_queries' => 100,
            'cache_hit_ratio' => 0.8, // 80%
        ];
    }
    
    /**
     * Set custom threshold for a metric
     */
    public function setThreshold(string $metric, float|int $value): void
    {
        $this->thresholds[$metric] = $value;
    }
    
    /**
     * Check current metrics against thresholds and generate alerts
     */
    public function checkAlerts(): array
    {
        $metrics = $this->monitor->collectMetrics();
        $this->alerts = [];
        
        foreach ($metrics as $name => $value) {
            if (isset($this->thresholds[$name])) {
                $threshold = $this->thresholds[$name];
                
                if ($this->isExceedingThreshold($value, $threshold)) {
                    $this->alerts[] = [
                        'metric' => $name,
                        'value' => $value,
                        'threshold' => $threshold,
                        'severity' => $this->calculateSeverity($value, $threshold),
                        'timestamp' => microtime(true),
                    ];
                }
            }
        }
        
        return $this->alerts;
    }
    
    /**
     * Determine if a metric value exceeds its threshold
     */
    private function isExceedingThreshold(mixed $value, float|int $threshold): bool
    {
        if (is_numeric($value)) {
            return $value > $threshold;
        }
        
        if (is_array($value) && isset($value['duration'])) {
            return $value['duration'] > $threshold;
        }
        
        if (is_array($value) && isset($value['memory'])) {
            return $value['memory'] > $threshold;
        }
        
        return false;
    }
    
    /**
     * Calculate alert severity based on how much threshold is exceeded
     */
    private function calculateSeverity(mixed $value, float|int $threshold): string
    {
        $numericValue = is_array($value) && isset($value['duration']) ? $value['duration'] : 
                       (is_array($value) && isset($value['memory']) ? $value['memory'] : 
                       (is_numeric($value) ? $value : 0));
        
        $ratio = $numericValue / $threshold;
        
        if ($ratio >= 2) {
            return 'critical';
        } elseif ($ratio >= 1.5) {
            return 'warning';
        } else {
            return 'info';
        }
    }
    
    /**
     * Get current alerts
     */
    public function getAlerts(): array
    {
        return $this->alerts;
    }
    
    /**
     * Clear alerts
     */
    public function clearAlerts(): void
    {
        $this->alerts = [];
    }
    
    /**
     * Register alert callback for notification
     */
    public function onAlert(callable $callback): void
    {
        // In a real implementation, this would register callbacks
        // for when alerts are triggered
    }
}