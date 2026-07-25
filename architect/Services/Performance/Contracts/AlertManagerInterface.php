<?php

namespace Architect\Services\Performance\Contracts;

interface AlertManagerInterface
{
    /**
     * Set custom threshold for a metric
     */
    public function setThreshold(string $metric, float|int $value): void;

    /**
     * Check current metrics against thresholds and generate alerts
     */
    public function checkAlerts(): array;

    /**
     * Get current alerts
     */
    public function getAlerts(): array;

    /**
     * Clear alerts
     */
    public function clearAlerts(): void;

    /**
     * Register alert callback for notification
     */
    public function onAlert(callable $callback): void;
}
