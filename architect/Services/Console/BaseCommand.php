<?php

declare(strict_types=1);

namespace Architect\Console;

use Exception;

/**
 * Base command class with common functionality
 */
abstract class BaseCommand implements CommandInterface
{
    protected string $name = '';
    protected string $description = '';
    protected OutputFormatter $output;
    protected Input $input;

    /** @var array<int, string> */
    protected array $aliases = [];

    public function __construct()
    {
        $this->output = new OutputFormatter();
    }

    /**
     * Initialize command (called before execution)
     */
    public function initialize(Input $input, OutputFormatter $output): void
    {
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }

    /**
     * {@inheritdoc}
     */
    public function requiresAuth(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions(): array
    {
        return [];
    }

    /**
     * Get output formatter
     */
    public function getOutput(): OutputFormatter
    {
        return $this->output;
    }

    /**
     * Get input
     */
    public function getInput(): Input
    {
        return $this->input;
    }

    /**
     * Run the command
     */
    public function run(Input $input): int
    {
        $this->initialize($input, $this->output);

        // Check for help
        if ($input->isHelp()) {
            return $this->showHelp();
        }

        // Validate required arguments
        if (!$this->validateArguments($input)) {
            return 1;
        }

        try {
            return $this->execute(
                $this->parseArguments($input),
                $input->getOptions()
            );
        } catch (Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Validate required arguments
     */
    protected function validateArguments(Input $input): bool
    {
        $arguments = $this->getArguments();
        $provided = $input->getArguments();

        foreach ($arguments as $index => $definition) {
            [$name, $description, $required] = $this->parseArgumentDefinition($definition);

            if ($required && !isset($provided[$index])) {
                $this->error("Missing required argument: {$name}");
                return false;
            }
        }

        return true;
    }

    /**
     * Parse argument definition
     *
     * @param array{0: string, 1: string, 2?: bool} $definition
     * @return array{0: string, 1: string, 2: bool}
     */
    protected function parseArgumentDefinition(array $definition): array
    {
        $name = $definition[0];
        $description = $definition[1] ?? '';
        $required = $definition[2] ?? false;

        return [$name, $description, $required];
    }

    /**
     * Parse arguments into associative array
     *
     * @return array<string, mixed>
     */
    protected function parseArguments(Input $input): array
    {
        $arguments = $this->getArguments();
        $provided = $input->getArguments();
        $result = [];

        foreach ($arguments as $index => $definition) {
            [$name] = $this->parseArgumentDefinition($definition);
            $result[$name] = $provided[$index] ?? null;
        }

        return $result;
    }

    /**
     * Show help for the command
     */
    public function showHelp(): int
    {
        $this->line();
        $this->header('Usage:');
        $this->line('  ' . $this->getUsage());
        $this->line();

        if ($this->getDescription()) {
            $this->header('Description:');
            $this->line('  ' . $this->getDescription());
            $this->line();
        }

        $arguments = $this->getArguments();
        if (!empty($arguments)) {
            $this->header('Arguments:');
            foreach ($arguments as $definition) {
                [$name, $description, $required] = $this->parseArgumentDefinition($definition);
                $requiredStr = $required ? ' (required)' : ' (optional)';
                $this->line("  {$name}{$requiredStr}");
                if ($description) {
                    $this->line("    {$description}");
                }
            }
            $this->line();
        }

        $options = $this->getOptions();
        if (!empty($options)) {
            $this->header('Options:');
            foreach ($options as $definition) {
                [$name, $description] = $this->parseOptionDefinition($definition);
                $this->line("  {$name}");
                if ($description) {
                    $this->line("    {$description}");
                }
            }
            $this->line();
        }

        $aliases = $this->getAliases();
        if (!empty($aliases)) {
            $this->header('Aliases:');
            $this->line('  ' . implode(', ', $aliases));
            $this->line();
        }

        return 0;
    }

    /**
     * Parse option definition
     *
     * @param array{0: string, 1: string} $definition
     * @return array{0: string, 1: string}
     */
    protected function parseOptionDefinition(array $definition): array
    {
        return [$definition[0], $definition[1] ?? ''];
    }

    /**
     * Get command usage string
     */
    public function getUsage(): string
    {
        $usage = $this->name;

        foreach ($this->getArguments() as $definition) {
            [$name, , $required] = $this->parseArgumentDefinition($definition);
            $usage .= $required ? " {$name}" : " [{$name}]";
        }

        return $usage;
    }

    /**
     * Print header message
     */
    protected function header(string $message): void
    {
        $this->output->line($this->output->header($message));
    }

    /**
     * Print info message
     */
    protected function info(string $message): void
    {
        $this->output->line($this->output->info($message));
    }

    /**
     * Print success message
     */
    protected function success(string $message): void
    {
        $this->output->line($this->output->success($message));
    }

    /**
     * Print warning message
     */
    protected function warning(string $message): void
    {
        $this->output->line($this->output->warning($message));
    }

    /**
     * Print error message
     */
    protected function error(string $message): void
    {
        $this->output->line($this->output->error($message));
    }

    /**
     * Print comment message
     */
    protected function comment(string $message): void
    {
        $this->output->line($this->output->comment($message));
    }

    /**
     * Print line
     */
    protected function line(string $message = ''): void
    {
        $this->output->line($message);
    }

    /**
     * Print table
     */
    protected function table(array $headers, array $rows): void
    {
        $this->output->line($this->output->table($headers, $rows));
    }

    /**
     * Ask for user input
     */
    protected function ask(string $question, string $default = ''): string
    {
        $question = $this->output->question($question);

        if ($default) {
            $question .= " [{$default}]";
        }

        $question .= ': ';

        echo $question;

        $input = trim(fgets(STDIN) ?: '');

        return $input ?: $default;
    }

    /**
     * Ask for confirmation (yes/no)
     */
    protected function confirm(string $question, bool $default = false): bool
    {
        $question = $this->output->question($question);
        $question .= ' ' . ($default ? '[Y/n]' : '[y/N]');
        $question .= ': ';

        echo $question;

        $input = strtolower(trim(fgets(STDIN) ?: ''));

        if (empty($input)) {
            return $default;
        }

        return in_array($input, ['y', 'yes'], true);
    }

    /**
     * Choice from multiple options
     */
    protected function choice(string $question, array $choices, int $default = 0): string
    {
        $this->line($this->output->question($question));

        foreach ($choices as $i => $choice) {
            $marker = ($i === $default) ? '*' : ' ';
            $this->line("  {$marker} {$choice}");
        }

        $answer = (int)$this->ask('Select option', (string)$default);

        return $choices[$answer] ?? $choices[$default];
    }

    /**
     * Progress bar callback
     *
     * @param callable(int): void $callback
     */
    protected function withProgress(int $total, callable $callback): void
    {
        for ($i = 0; $i <= $total; $i++) {
            $percent = $total > 0 ? (int)(($i / $total) * 100) : 100;
            echo "\r" . $this->output->progressBar($i, $total);
            $callback($i);
        }
        echo PHP_EOL;
    }
}
