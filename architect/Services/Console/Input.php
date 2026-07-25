<?php

declare(strict_types=1);

namespace Architect\Console;

/**
 * Console input parser for arguments and options
 */
class Input
{
    /** @var array<int, string> */
    protected array $arguments = [];

    /** @var array<string, mixed> */
    protected array $options = [];

    /** @var array<int, string> */
    protected array $tokens = [];

    protected string $command = '';

    public function __construct(array $argv = [])
    {
        if (empty($argv)) {
            $argv = $_SERVER['argv'] ?? [];
        }

        $this->parse($argv);
    }

    /**
     * Parse command line arguments
     *
     * @param array<int, string> $argv
     */
    protected function parse(array $argv): void
    {
        // Remove script name (first element)
        array_shift($argv);

        $this->tokens = $argv;

        if (empty($argv)) {
            return;
        }

        // First token is the command name (without the "arc" prefix)
        $this->command = array_shift($argv);

        // Parse remaining tokens
        $this->arguments = [];
        $this->options = [];

        $i = 0;
        while ($i < count($argv)) {
            $token = $argv[$i];

            // Option (starts with -- or -)
            if (str_starts_with($token, '--')) {
                $option = substr($token, 2);

                // Check for --option=value format
                if (($pos = strpos($option, '=')) !== false) {
                    $key = substr($option, 0, $pos);
                    $value = substr($option, $pos + 1);
                    $this->options[$key] = $value;
                } else {
                    // Check if next token is a value (not an option)
                    if (isset($argv[$i + 1]) && !str_starts_with($argv[$i + 1], '-')) {
                        $this->options[$option] = $argv[++$i];
                    } else {
                        $this->options[$option] = true;
                    }
                }
            } elseif (str_starts_with($token, '-')) {
                // Short option (e.g., -f)
                $option = substr($token, 1);

                if (isset($argv[$i + 1]) && !str_starts_with($argv[$i + 1], '-')) {
                    $this->options[$option] = $argv[++$i];
                } else {
                    $this->options[$option] = true;
                }
            } else {
                // Positional argument
                $this->arguments[] = $token;
            }

            $i++;
        }
    }

    /**
     * Get command name
     */
    public function getCommand(): string
    {
        return $this->command;
    }

    /**
     * Get all positional arguments
     *
     * @return array<int, string>
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Get argument by index
     */
    public function getArgument(int $index, mixed $default = null): mixed
    {
        return $this->arguments[$index] ?? $default;
    }

    /**
     * Get all options
     *
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Get option value
     */
    public function getOption(string $name, mixed $default = null): mixed
    {
        return $this->options[$name] ?? $default;
    }

    /**
     * Check if option exists
     */
    public function hasOption(string $name): bool
    {
        return isset($this->options[$name]);
    }

    /**
     * Check if option is truthy (true, 'true', '1', 'yes')
     */
    public function isOption(string $name): bool
    {
        $value = $this->options[$name] ?? false;

        if ($value === true) {
            return true;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['true', '1', 'yes', 'on'], true);
        }

        return (bool)$value;
    }

    /**
     * Get all raw tokens
     *
     * @return array<int, string>
     */
    public function getTokens(): array
    {
        return $this->tokens;
    }

    /**
     * Check if help is requested
     */
    public function isHelp(): bool
    {
        return $this->isOption('help') || $this->isOption('h');
    }

    /**
     * Check if verbose mode is enabled
     */
    public function isVerbose(): bool
    {
        return $this->isOption('verbose') || $this->isOption('v');
    }

    /**
     * Check if quiet mode is enabled
     */
    public function isQuiet(): bool
    {
        return $this->isOption('quiet') || $this->isOption('q');
    }
}
