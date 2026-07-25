<?php

declare(strict_types=1);

namespace Architect\Console;

use Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Console Kernel - main entry point for CLI commands
 */
class ConsoleKernel
{
    protected CommandRegistry $registry;
    protected OutputFormatter $output;
    protected ?Input $input = null;

    protected bool $running = false;
    protected array $bootstrappers = [];

    protected LoggerInterface $logger;

    public function __construct(
        ?CommandRegistry $registry = null,
        ?OutputFormatter $output = null,
        ?LoggerInterface $logger = null
    ) {
        $this->registry = $registry ?? new CommandRegistry();
        $this->output = $output ?? new OutputFormatter();
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Set logger instance (PSR-3 compatible)
     */
    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Get logger instance
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Bootstrap the console application
     */
    public function bootstrap(): void
    {
        if ($this->running) {
            return;
        }

        $this->running = true;

        // Run bootstrappers
        foreach ($this->bootstrappers as $bootstrap) {
            if (is_callable($bootstrap)) {
                $bootstrap($this);
            }
        }

        // Load cached commands if available
        if ($this->registry->isCacheEnabled()) {
            $this->registry->loadCache();
        }
    }

    /**
     * Add a bootstrapper
     */
    public function addBootstrapper(callable $bootstrapper): self
    {
        $this->bootstrappers[] = $bootstrapper;

        return $this;
    }

    /**
     * Run the console application
     */
    public function run(?Input $input = null): int
    {
        $this->bootstrap();

        $this->input = $input ?? new Input();

        // Log the command execution
        $this->logger->info('Console execution started', [
            'command' => $this->input->getCommand() ?? '(empty)',
            'arguments' => $this->input->getArguments(),
            'options' => $this->input->getOptions(),
        ]);

        // Handle empty command
        if (empty($this->input->getCommand())) {
            return $this->handleEmptyCommand();
        }

        $commandName = $this->input->getCommand();

        // Handle built-in commands
        if ($commandName === 'list' || $commandName === 'help') {
            return $this->handleListCommand();
        }

        // Find and execute command
        $command = $this->registry->get($commandName);

        if (!$command) {
            $this->output->error("Command not found: {$commandName}");
            $this->output->line();
            $this->output->line("Run 'arc list' to see available commands.");

            $this->logger->warning("Command not found: {$commandName}");

            return 1;
        }

        // Run the command
        $exitCode = $this->runCommand($command);

        // Log completion
        $this->logger->info('Console execution completed', [
            'command' => $commandName,
            'exit_code' => $exitCode,
        ]);

        return $exitCode;
    }

    /**
     * Run a command
     */
    public function runCommand(CommandInterface $command, ?Input $input = null): int
    {
        $input ??= $this->input;

        // Check for help on specific command
        if ($input->isHelp()) {
            $command->initialize($input, $this->output);
            return $command->showHelp();
        }

        try {
            return $command->run($input);
        } catch (Exception $e) {
            $this->output->error($e->getMessage());

            if ($input->isVerbose()) {
                $this->output->line($e->getTraceAsString());
            }

            return 1;
        }
    }

    /**
     * Handle empty command (show welcome/usage)
     */
    protected function handleEmptyCommand(): int
    {
        $this->output->line($this->output->header('Architect Console'));
        $this->output->line();
        $this->output->line('Usage:');
        $this->output->line('  arc <command> [arguments] [options]');
        $this->output->line();
        $this->output->line("Run 'arc list' to see available commands.");

        return 0;
    }

    /**
     * Handle list command - show all available commands
     */
    protected function handleListCommand(): int
    {
        $commands = $this->registry->all();

        if (empty($commands)) {
            $this->output->warning('No commands registered.');
            return 0;
        }

        $this->output->line($this->output->header('Available Commands:'));
        $this->output->line();

        $grouped = $this->registry->getGrouped();

        foreach ($grouped as $group => $groupCommands) {
            $this->output->line($this->output->info(ucfirst($group) . ' commands:'));

            $rows = [];
            foreach ($groupCommands as $name => $command) {
                $rows[] = [
                    '  ' . $name,
                    $command->getDescription(),
                ];
            }

            $this->output->line($this->output->table(['Command', 'Description'], $rows));
            $this->output->line();
        }

        $this->output->line($this->output->comment('Use arc <command> --help for detailed information.'));

        return 0;
    }

    /**
     * Register a command
     */
    public function registerCommand(CommandInterface $command): self
    {
        $this->registry->register($command);

        return $this;
    }

    /**
     * Register multiple commands
     *
     * @param array<int, CommandInterface> $commands
     */
    public function registerCommands(array $commands): self
    {
        $this->registry->registerCommands($commands);

        return $this;
    }

    /**
     * Get the command registry
     */
    public function getRegistry(): CommandRegistry
    {
        return $this->registry;
    }

    /**
     * Get output formatter
     */
    public function getOutput(): OutputFormatter
    {
        return $this->output;
    }

    /**
     * Get current input
     */
    public function getInput(): Input
    {
        return $this->input;
    }

    /**
     * Set output formatter
     */
    public function setOutput(OutputFormatter $output): self
    {
        $this->output = $output;

        return $this;
    }

    /**
     * Enable/disable colors
     */
    public function setColorsEnabled(bool $enabled): self
    {
        $this->output->setColors($enabled);

        return $this;
    }

    /**
     * Set cache path for commands
     */
    public function setCachePath(string $path): self
    {
        $this->registry->setCacheEnabled(true);

        return $this;
    }

    /**
     * Build cache for commands
     */
    public function buildCache(): bool
    {
        return $this->registry->saveCache();
    }

    /**
     * Find commands by partial name
     *
     * @return array<string, CommandInterface>
     */
    public function findCommands(string $pattern): array
    {
        return $this->registry->find($pattern);
    }
}
