<?php

declare(strict_types=1);

namespace Architect\Support;

use Architect\Services\Debug\Contracts\DebugCollectorInterface;

/**
 * Static facade for DebugDataCollector.
 * Provides convenient access to debug data collection from anywhere in the code.
 *
 * Usage:
 * Debug::addMessage('category', 'message', 'info', ['key' => 'value']);
 * Debug::startTimer('timer_name', 'category');
 * Debug::stopTimer('timer_name');
 * Debug::addData('category', ['key' => 'value'], 'description');
 * Debug::incrementCounter('category', 'counter_name', 1);
 * Debug::markEvent('event_name', ['meta' => 'data']);
 * Debug::setMetadata('key', 'value');
 *
 * @method static void addMessage(string $category, string $message, string $level = 'info', array $context = [])
 * @method static void startTimer(string $name, string $category = 'performance')
 * @method static float|null stopTimer(string $name)
 * @method static void addData(string $category, $data, string $description = '')
 * @method static void incrementCounter(string $category, string $counterName, int $value = 1)
 * @method static void markEvent(string $eventName, array $metadata = [])
 * @method static void setMetadata(string $key, $value)
 */
class Debug
{
    private static ?DebugCollectorInterface $collector = null;

    public static function setCollector(DebugCollectorInterface $collector): void
    {
        self::$collector = $collector;
    }

    public static function getCollector(): ?DebugCollectorInterface
    {
        return self::$collector;
    }

    public static function isAvailable(): bool
    {
        return self::$collector !== null;
    }

    public static function __callStatic(string $method, array $args): mixed
    {
        if (self::$collector === null) {
            return null;
        }

        return call_user_func_array([self::$collector, $method], $args);
    }

    public static function log(string $message, string $level = 'info', array $context = []): void
    {
        self::addMessage('general', $message, $level, $context);
    }

    public static function dump(string $label, $data): void
    {
        self::addData('dump', $data, $label);
    }
}
