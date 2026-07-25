<?php

declare(strict_types=1);

namespace Architect\Services\Debug;

use Architect\Services\Debug\Contracts\DebugCollectorInterface;
use Architect\Services\Debug\Traits\DebugFormatterTrait;

/**
 * Collects user debug data from any part of the application.
 * Supports messages, timers, structured data, counters, events, and metadata.
 */
class DebugDataCollector implements DebugCollectorInterface
{
    use DebugFormatterTrait;

    private const MAX_MESSAGES = 1000;

    private array $messages = [];
    private array $timers = [];
    private array $data = [];
    private array $counters = [];
    private array $events = [];
    private array $metadata = [];
    private bool $hasData = false;
    private bool $newDataSinceRender = false;

    public function addMessage(string $category, string $message, string $level = 'info', array $context = []): void
    {
        if (count($this->messages) >= self::MAX_MESSAGES) {
            return;
        }
        
        $this->messages[] = [
            'time' => microtime(true),
            'category' => $category,
            'message' => $message,
            'level' => $level,
            'context' => $this->sanitizeData($this->truncateData($context)),
        ];
        
        $this->hasData = true;
        $this->newDataSinceRender = true;
    }

    public function startTimer(string $name, string $category = 'performance'): void
    {
        $this->timers[$name] = [
            'category' => $category,
            'start' => microtime(true),
            'end' => null,
            'duration' => null,
        ];
        
        $this->hasData = true;
    }

    public function stopTimer(string $name): ?float
    {
        if (!isset($this->timers[$name])) {
            return null;
        }
        
        $end = microtime(true);
        $this->timers[$name]['end'] = $end;
        $this->timers[$name]['duration'] = $end - $this->timers[$name]['start'];
        
        $this->hasData = true;
        $this->newDataSinceRender = true;
        
        return $this->timers[$name]['duration'];
    }

    public function addData(string $category, $data, string $description = ''): void
    {
        $serialized = serialize($data);

        if (strlen($serialized) > self::MAX_DATA_SIZE) {
            $data = ['_truncated' => true, 'size' => strlen($serialized)];
        }
        
        if (!isset($this->data[$category])) {
            $this->data[$category] = [];
        }
        
        $this->data[$category][] = [
            'time' => microtime(true),
            'description' => $description,
            'data' => $this->sanitizeData($this->flattenData($data)),
        ];
        
        $this->hasData = true;
        $this->newDataSinceRender = true;
    }

    public function incrementCounter(string $category, string $counterName, int $value = 1): void
    {
        $key = "{$category}:{$counterName}";
        
        if (!isset($this->counters[$key])) {
            $this->counters[$key] = [
                'category' => $category,
                'name' => $counterName,
                'value' => 0,
                'history' => [],
            ];
        }
        
        $this->counters[$key]['value'] += $value;
        $this->counters[$key]['history'][] = [
            'time' => microtime(true),
            'delta' => $value,
        ];
        
        $this->hasData = true;
    }

    public function markEvent(string $eventName, array $metadata = []): void
    {
        $this->events[] = [
            'time' => microtime(true),
            'name' => $eventName,
            'metadata' => $this->sanitizeData($metadata),
        ];
        
        $this->hasData = true;
        $this->newDataSinceRender = true;
    }

    public function setMetadata(string $key, $value): void
    {
        $this->metadata[$key] = $value;
        $this->hasData = true;
    }

    public function clear(): void
    {
        $this->messages = [];
        $this->timers = [];
        $this->data = [];
        $this->counters = [];
        $this->events = [];
        $this->metadata = [];
        $this->hasData = false;
        $this->newDataSinceRender = false;
    }

    public function getData(): array
    {
        $timers = array_map(function ($timer) {
            if ($timer['duration'] === null) {
                $timer['duration'] = microtime(true) - $timer['start'];
                $timer['status'] = 'unfinished';
            } else {
                $timer['status'] = 'finished';
            }
            return $timer;
        }, $this->timers);
        
        $messagesByCategory = [];
        foreach ($this->messages as $msg) {
            $cat = $msg['category'];
            if (!isset($messagesByCategory[$cat])) {
                $messagesByCategory[$cat] = [];
            }
            $messagesByCategory[$cat][] = $msg;
        }
        
        $levelStats = [
            'debug' => 0,
            'info' => 0,
            'warning' => 0,
            'error' => 0,
        ];
        foreach ($this->messages as $msg) {
            $level = $msg['level'];
            if (isset($levelStats[$level])) {
                $levelStats[$level]++;
            }
        }
        
        $sortedTimers = $timers;
        usort($sortedTimers, fn($a, $b) => ($b['duration'] ?? 0) - ($a['duration'] ?? 0));
        $topTimers = array_slice($sortedTimers, 0, 5);
        
        $sortedCounters = array_values($this->counters);
        usort($sortedCounters, fn($a, $b) => $b['value'] - $a['value']);
        $topCounters = array_slice($sortedCounters, 0, 5);
        
        return [
            'has_data' => $this->hasData,
            'new_data' => $this->newDataSinceRender,
            'messages' => $this->messages,
            'messages_by_category' => $messagesByCategory,
            'level_stats' => $levelStats,
            'timers' => $timers,
            'top_timers' => $topTimers,
            'data' => $this->data,
            'counters' => $this->counters,
            'top_counters' => $topCounters,
            'events' => $this->events,
            'metadata' => $this->metadata,
            'category_count' => count($messagesByCategory) + count($this->data),
            'total_messages' => count($this->messages),
        ];
    }
    
    public function resetNewDataFlag(): void
    {
        $this->newDataSinceRender = false;
    }

    public function getCategoryCount(): int
    {
        $categories = count(array_unique(array_map(fn($m) => $m['category'], $this->messages)));
        $categories += count($this->data);
        return $categories;
    }
    
    public function getTotalMessages(): int
    {
        return count($this->messages);
    }
}
