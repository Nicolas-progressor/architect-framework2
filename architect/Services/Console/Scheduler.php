<?php

declare(strict_types=1);

namespace Architect\Console;

use Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Task scheduler for console commands
 *
 * Usage:
 * $schedule->command('cache:clear')->daily();
 * $schedule->command('db:migrate')->weekly()->sundays();
 */
class Scheduler
{
    /** @var array<int, ScheduledTask> */
    protected array $tasks = [];

    protected LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Set logger instance
     */
    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Schedule a command
     */
    public function command(string $command, array $arguments = []): ScheduledTask
    {
        $task = new ScheduledTask($command, $arguments);
        $this->tasks[] = $task;

        $this->logger->debug("Scheduled command: {$command}", [
            'arguments' => $arguments,
        ]);

        return $task;
    }

    /**
     * Schedule a closure/callback
     */
    public function call(callable $callback, array $parameters = []): ScheduledTask
    {
        $task = new ScheduledTask(null, $parameters);
        $task->setCallback($callback);
        $this->tasks[] = $task;

        $this->logger->debug('Scheduled closure task', [
            'parameters' => $parameters,
        ]);

        return $task;
    }

    /**
     * Get all scheduled tasks
     *
     * @return array<int, ScheduledTask>
     */
    public function tasks(): array
    {
        return $this->tasks;
    }

    /**
     * Get tasks due to run
     *
     * @return array<int, ScheduledTask>
     */
    public function dueTasks(): array
    {
        $due = [];
        $now = new \DateTime();

        foreach ($this->tasks as $task) {
            if ($task->isDue($now)) {
                $due[] = $task;
            }
        }

        return $due;
    }

    /**
     * Run all due tasks
     */
    public function run(?ConsoleKernel $console = null): int
    {
        $due = $this->dueTasks();

        if (empty($due)) {
            $this->logger->debug('No tasks due to run');

            return 0;
        }

        $console ??= new ConsoleKernel();
        $exitCode = 0;

        $this->logger->info('Running scheduled tasks', [
            'count' => count($due),
        ]);

        foreach ($due as $task) {
            $taskName = $task->getName();

            $this->logger->info("Running scheduled task: {$taskName}");

            try {
                $result = $task->run($console);

                if ($result !== 0) {
                    $this->logger->warning("Task failed: {$taskName}", [
                        'exit_code' => $result,
                    ]);
                    $exitCode = $result;
                } else {
                    $this->logger->info("Task completed: {$taskName}");
                }
            } catch (Exception $e) {
                $this->logger->error("Task exception: {$taskName}", [
                    'message' => $e->getMessage(),
                ]);
                $console->getOutput()->error('Task failed: ' . $e->getMessage());
                $exitCode = 1;
            }
        }

        return $exitCode;
    }
}
