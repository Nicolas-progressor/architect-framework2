<?php

declare(strict_types=1);

namespace Architect\Console;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Registry for console commands
 */
class CommandRegistry
{
    /** @var array<string, CommandInterface> */
    protected array $commands = [];

    /** @var array<string, string> Command aliases to command names */
    protected array $aliases = [];

    protected ?string $cachePath = null;
    protected bool $useCache = false;

    protected LoggerInterface $logger;

    public function __construct(?string $cachePath = null, ?LoggerInterface $logger = null)
    {
        $this->cachePath = $cachePath;
        $this->useCache = $cachePath !== null;
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
     * Get logger instance
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Register a command
     *
     * @throws InvalidArgumentException
     */
    public function register(CommandInterface $command): self
    {
        $name = $command->getName();

        if (empty($name)) {
            throw new InvalidArgumentException('Command name cannot be empty');
        }

        if (isset($this->commands[$name])) {
            throw new InvalidArgumentException("Command '{$name}' is already registered");
        }

        $this->commands[$name] = $command;

        $this->logger->debug("Command registered: {$name}", [
            'description' => $command->getDescription(),
            'aliases' => $command->getAliases(),
        ]);

        // Register aliases
        foreach ($command->getAliases() as $alias) {
            $this->aliases[$alias] = $name;
            $this->logger->debug("Command alias registered: {$alias} -> {$name}");
        }

        return $this;
    }

    /**
     * Register multiple commands
     *
     * @param array<int, CommandInterface> $commands
     */
    public function registerCommands(array $commands): self
    {
        foreach ($commands as $command) {
            $this->register($command);
        }

        return $this;
    }

    /**
     * Remove a command
     */
    public function unregister(string $name): self
    {
        if (isset($this->commands[$name])) {
            // Remove aliases
            foreach ($this->commands[$name]->getAliases() as $alias) {
                unset($this->aliases[$alias]);
            }

            unset($this->commands[$name]);
        }

        return $this;
    }

    /**
     * Get a command by name
     */
    public function get(string $name): ?CommandInterface
    {
        // Resolve alias
        if (isset($this->aliases[$name])) {
            $name = $this->aliases[$name];
        }

        return $this->commands[$name] ?? null;
    }

    /**
     * Check if command exists
     */
    public function has(string $name): bool
    {
        return isset($this->commands[$name]) || isset($this->aliases[$name]);
    }

    /**
     * Get all registered commands
     *
     * @return array<string, CommandInterface>
     */
    public function all(): array
    {
        return $this->commands;
    }

    /**
     * Get all command names
     *
     * @return array<int, string>
     */
    public function getNames(): array
    {
        return array_keys($this->commands);
    }

    /**
     * Find commands matching a pattern
     *
     * @return array<string, CommandInterface>
     */
    public function find(string $pattern): array
    {
        $matches = [];
        $pattern = strtolower($pattern);

        foreach ($this->commands as $name => $command) {
            if (str_contains($name, $pattern)) {
                $matches[$name] = $command;
            }
        }

        return $matches;
    }

    /**
     * Get command by alias
     */
    public function resolveAlias(string $alias): ?CommandInterface
    {
        if (isset($this->aliases[$alias])) {
            return $this->commands[$this->aliases[$alias]] ?? null;
        }

        return null;
    }

    /**
     * Get command count
     */
    public function count(): int
    {
        return count($this->commands);
    }

    /**
     * Clear all commands
     */
    public function clear(): self
    {
        $this->commands = [];
        $this->aliases = [];

        return $this;
    }

    /**
     * Enable/disable cache
     */
    public function setCacheEnabled(bool $enabled): self
    {
        $this->useCache = $enabled;

        return $this;
    }

    /**
     * Get cache enabled status
     */
    public function isCacheEnabled(): bool
    {
        return $this->useCache;
    }

    /**
     * Save commands to cache
     */
    public function saveCache(): bool
    {
        if (!$this->useCache || !$this->cachePath) {
            return false;
        }

        $data = [
            'commands' => [],
            'aliases' => $this->aliases,
        ];

        foreach ($this->commands as $name => $command) {
            $data['commands'][$name] = [
                'class' => get_class($command),
                'name' => $command->getName(),
                'description' => $command->getDescription(),
            ];
        }

        $dir = dirname($this->cachePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0o755, true);
        }

        $result = file_put_contents(
            $this->cachePath,
            '<?php return ' . var_export($data, true) . ';'
        );

        if ($result !== false) {
            $this->logger->info('Command cache saved', [
                'path' => $this->cachePath,
                'commands_count' => count($this->commands),
            ]);
        }

        return $result !== false;
    }

    /**
     * Load commands from cache
     *
     * Note: Commands are not fully restored from cache - only aliases.
     * Commands must be re-registered after loading cache.
     * This is a performance optimization for alias resolution only.
     */
    public function loadCache(): bool
    {
        if (!$this->useCache || !$this->cachePath || !file_exists($this->cachePath)) {
            return false;
        }

        $data = require $this->cachePath;

        if (!is_array($data)) {
            $this->logger->warning('Invalid command cache file', [
                'path' => $this->cachePath,
            ]);

            return false;
        }

        $this->aliases = $data['aliases'] ?? [];

        $this->logger->debug('Command cache loaded', [
            'aliases_count' => count($this->aliases),
        ]);

        return true;
    }

    /**
     * Get commands grouped by namespace
     *
     * @return array<string, array<string, CommandInterface>>
     */
    public function getGrouped(): array
    {
        $groups = [];

        foreach ($this->commands as $name => $command) {
            $parts = explode(':', $name, 2);
            $group = $parts[0] ?? 'default';
            $groups[$group][$name] = $command;
        }

        ksort($groups);

        return $groups;
    }
}
