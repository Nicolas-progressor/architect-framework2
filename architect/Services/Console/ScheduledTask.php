<?php

declare(strict_types=1);

namespace Architect\Console;

use Exception;

/**
 * Represents a scheduled task for the scheduler
 */
class ScheduledTask
{
    protected ?string $command;
    protected array $arguments;
    protected mixed $callback = null;

    protected ?string $expression = null;
    protected ?\DateTime $startTime = null;
    protected ?\DateTime $endTime = null;
    protected bool $withoutOverlapping = false;
    protected ?string $name = null;

    public function __construct(?string $command, array $arguments = [])
    {
        $this->command = $command;
        $this->arguments = $arguments;
    }

    /**
     * Set cron expression
     */
    public function cron(string $expression): self
    {
        $this->expression = $expression;

        return $this;
    }

    /**
     * Run every minute
     */
    public function everyMinute(): self
    {
        return $this->cron('* * * * *');
    }

    /**
     * Run every five minutes
     */
    public function everyFiveMinutes(): self
    {
        return $this->cron('*/5 * * * *');
    }

    /**
     * Run every fifteen minutes
     */
    public function everyFifteenMinutes(): self
    {
        return $this->cron('*/15 * * * *');
    }

    /**
     * Run every thirty minutes
     */
    public function everyThirtyMinutes(): self
    {
        return $this->cron('*/30 * * * *');
    }

    /**
     * Run hourly
     */
    public function hourly(): self
    {
        return $this->cron('0 * * * *');
    }

    /**
     * Run daily at midnight
     */
    public function daily(): self
    {
        return $this->cron('0 0 * * *');
    }

    /**
     * Run daily at specific time
     */
    public function dailyAt(string $time): self
    {
        [$hour, $minute] = explode(':', $time);

        return $this->cron("{$minute} {$hour} * * *");
    }

    /**
     * Run weekly
     */
    public function weekly(): self
    {
        return $this->cron('0 0 * * 0');
    }

    /**
     * Run on specific days
     */
    public function sundays(): self
    {
        return $this->days(0);
    }

    public function mondays(): self
    {
        return $this->days(1);
    }

    public function tuesdays(): self
    {
        return $this->days(2);
    }

    public function wednesdays(): self
    {
        return $this->days(3);
    }

    public function thursdays(): self
    {
        return $this->days(4);
    }

    public function fridays(): self
    {
        return $this->days(5);
    }

    public function saturdays(): self
    {
        return $this->days(6);
    }

    /**
     * Run on specific days of the week
     *
     * @param array<int, int>|int $days
     */
    public function days(array|int $days): self
    {
        $daysStr = is_array($days) ? implode(',', $days) : (string)$days;

        // Modify cron expression to add days
        if ($this->expression) {
            $parts = explode(' ', $this->expression);
            if (count($parts) >= 5) {
                $parts[4] = $daysStr;
                $this->expression = implode(' ', $parts);
            }
        }

        return $this;
    }

    /**
     * Run monthly
     */
    public function monthly(): self
    {
        return $this->cron('0 0 1 * *');
    }

    /**
     * Run yearly
     */
    public function yearly(): self
    {
        return $this->cron('0 0 1 1 *');
    }

    /**
     * Set task name
     */
    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Run without overlapping
     */
    public function withoutOverlapping(): self
    {
        $this->withoutOverlapping = true;

        return $this;
    }

    /**
     * Set start time
     */
    public function between(\DateTime $start, \DateTime $end): self
    {
        $this->startTime = $start;
        $this->endTime = $end;

        return $this;
    }

    /**
     * Set callback
     */
    public function setCallback(callable $callback): self
    {
        $this->callback = $callback;

        return $this;
    }

    /**
     * Check if task is due
     */
    public function isDue(\DateTime $now): bool
    {
        if (!$this->expression) {
            return false;
        }

        // Simple cron check (for more complex scenarios, use a cron library)
        return $this->checkCron($this->expression, $now);
    }

    /**
     * Simple cron expression checker
     */
    protected function checkCron(string $expression, \DateTime $now): bool
    {
        $parts = explode(' ', trim($expression));

        if (count($parts) !== 5) {
            return false;
        }

        [$minute, $hour, $day, $month, $weekday] = $parts;

        $currentMinute = (int)$now->format('i');
        $currentHour = (int)$now->format('H');
        $currentDay = (int)$now->format('d');
        $currentMonth = (int)$now->format('m');
        $currentWeekday = (int)$now->format('w');

        return $this->matchCronPart($minute, $currentMinute)
            && $this->matchCronPart($hour, $currentHour)
            && $this->matchCronPart($day, $currentDay)
            && $this->matchCronPart($month, $currentMonth)
            && $this->matchCronPart($weekday, $currentWeekday);
    }

    /**
     * Match a single cron part
     */
    protected function matchCronPart(string $pattern, int $value): bool
    {
        // Wildcard
        if ($pattern === '*') {
            return true;
        }

        // Step (*/5)
        if (str_starts_with($pattern, '*/')) {
            $step = (int)substr($pattern, 2);

            return $value % $step === 0;
        }

        // Range (1-5)
        if (str_contains($pattern, '-')) {
            [$start, $end] = explode('-', $pattern);

            return $value >= (int)$start && $value <= (int)$end;
        }

        // List (1,2,3)
        if (str_contains($pattern, ',')) {
            $values = array_map('intval', explode(',', $pattern));

            return in_array($value, $values, true);
        }

        // Exact match
        return (int)$pattern === $value;
    }

    /**
     * Run the task
     */
    public function run(ConsoleKernel $console): int
    {
        if ($this->callback !== null && is_callable($this->callback)) {
            return ($this->callback)();
        }

        if (!$this->command) {
            return 1;
        }

        // Create input for the command
        $input = new Input([$this->command, ...$this->arguments]);

        // Run the command
        $command = $console->getRegistry()->get($this->command);

        if (!$command) {
            $console->getOutput()->error("Command not found: {$this->command}");

            return 1;
        }

        return $console->runCommand($command, $input);
    }

    /**
     * Get task name
     */
    public function getName(): string
    {
        return $this->name ?? $this->command ?? 'closure';
    }
}
