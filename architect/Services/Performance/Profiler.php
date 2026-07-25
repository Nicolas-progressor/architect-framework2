<?php

declare(strict_types=1);

namespace Architect\Services\Performance;

use Architect\Services\Performance\Contracts\MetricCollectorInterface;

/**
 * Simple profiler for measuring execution time of code blocks.
 * Integrates with the performance monitoring system.
 */
class Profiler
{
    private array $timers = [];
    private array $measurements = [];

    public function __construct(
        private ?MetricCollectorInterface $collector = null
    ) {}

    /**
     * Start measuring a named block.
     */
    public function start(string $name): void
    {
        $this->timers[$name] = [
            'start' => microtime(true),
            'memory_start' => memory_get_usage(true),
        ];
    }

    /**
     * Stop measuring a named block and record the result.
     */
    public function stop(string $name): array
    {
        if (!isset($this->timers[$name])) {
            throw new \RuntimeException("Timer '$name' was not started");
        }

        $timer = $this->timers[$name];
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);

        $measurement = [
            'name' => $name,
            'duration' => $endTime - $timer['start'],
            'memory_used' => $endMemory - $timer['memory_start'],
            'peak_memory' => $peakMemory,
            'start_time' => $timer['start'],
            'end_time' => $endTime,
        ];

        $this->measurements[$name] = $measurement;

        // Send to metric collector if available
        if ($this->collector !== null) {
            $this->collector->recordProfilerMeasurement($name, $measurement['duration'], $measurement['memory_used']);
        }

        unset($this->timers[$name]);

        return $measurement;
    }

    /**
     * Measure execution of a callable.
     */
    public function measure(string $name, callable $callback): mixed
    {
        $this->start($name);

        try {
            $result = $callback();
        } finally {
            $this->stop($name);
        }

        return $result;
    }

    /**
     * Get all measurements.
     */
    public function getMeasurements(): array
    {
        return $this->measurements;
    }

    /**
     * Get measurement for a specific block.
     */
    public function getMeasurement(string $name): ?array
    {
        return $this->measurements[$name] ?? null;
    }

    /**
     * Get total execution time of all measured blocks.
     */
    public function getTotalTime(): float
    {
        $total = 0.0;

        foreach ($this->measurements as $measurement) {
            $total += $measurement['duration'];
        }

        return $total;
    }

    /**
     * Get the slowest measurements.
     */
    public function getSlowest(int $limit = 10): array
    {
        $measurements = $this->measurements;

        usort($measurements, function ($a, $b) {
            return $b['duration'] <=> $a['duration'];
        });

        return array_slice($measurements, 0, $limit);
    }

    /**
     * Clear all measurements.
     */
    public function clear(): void
    {
        $this->timers = [];
        $this->measurements = [];
    }

    /**
     * Create a report of all measurements.
     */
    public function createReport(): array
    {
        $totalTime = $this->getTotalTime();
        $slowest = $this->getSlowest(5);

        return [
            'total_measurements' => count($this->measurements),
            'total_time' => $totalTime,
            'average_time' => count($this->measurements) > 0 ? $totalTime / count($this->measurements) : 0,
            'slowest' => $slowest,
            'measurements' => $this->measurements,
        ];
    }

    /**
     * Check if a timer is currently running.
     */
    public function isRunning(string $name): bool
    {
        return isset($this->timers[$name]);
    }
}
