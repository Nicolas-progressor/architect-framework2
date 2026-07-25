<?php

declare(strict_types=1);

namespace Architect\Core;

use Architect\Core\Contracts\ContainerInterface;
use Architect\Core\Contracts\StatementInterface;
use Architect\Core\Http\RequestDetector;
use Architect\Core\Debug\DebugPanelRenderer;
use Architect\Core\Exception\HttpNotFoundException;

/**
 * Statement manager for lifecycle hooks.
 * 
 * Manages application lifecycle stages and allows registering
 * callbacks to be executed at specific points.
 */
class Statement implements StatementInterface
{
    /** @var array<string, array> Registered hooks by statement */
    private array $hooks = [
        'core_preinit'   => [], 
        'core_init'      => [], 
        'core_load'      => [], 
        'core_post_load' => [], 
        'app_load'       => [], 
        'app_data'       => [], 
        'app_output'     => [], 
        'render'         => [],
    ];
    
    /** @var array<string, bool> Executed statements */
    private array $executed = [];

    /**
     * Create statement manager.
     */
    public function __construct(
        private ContainerInterface $container,
        private ?RequestDetector $requestDetector = null,
        private ?DebugPanelRenderer $debugRenderer = null
    ) {
        $this->requestDetector ??= new RequestDetector();
        $this->debugRenderer ??= new DebugPanelRenderer($container);
    }

    /**
     * Register a callback for a statement.
     */
    public function on(string $statement, callable $callback, int $priority = 10): void
    {
        if (!isset($this->hooks[$statement])) {
            $this->hooks[$statement] = [];
        }
        
        $this->hooks[$statement][] = [
            'callback' => $callback,
            'priority' => $priority,
        ];

        usort($this->hooks[$statement], fn($a, $b) => $a['priority'] <=> $b['priority']);
    }

    /**
     * Run all callbacks for a statement.
     */
    public function run(string $statement): void
    {
        if (!isset($this->hooks[$statement])) {
            return;
        }

        $this->executed[$statement] = true;

        // Start debug stage timer
        $this->container->get('debug')->startStage($statement);

        foreach ($this->hooks[$statement] as $hook) {
            $hook['callback']($this->container);
        }

        // End debug stage timer
        $this->container->get('debug')->endStage();
    }

    /**
     * Run all statements in order.
     */
    public function runAll(): void
    {
        foreach (array_keys($this->hooks) as $statement) {
            $this->run($statement);
        }

        $this->debugRenderer->render();
    }

    /**
     * Get all available statement names.
     */
    public function getStatements(): array
    {
        return array_keys($this->hooks);
    }

    /**
     * Check if statement was executed.
     */
    public function isExecuted(string $statement): bool
    {
        return isset($this->executed[$statement]);
    }
}
