<?php

declare(strict_types=1);

namespace Examples\Bundle\ExampleBundle\Service;

/**
 * Example service for demonstration.
 */
class ExampleService
{
    /** @var bool */
    private bool $initialized = false;

    /**
     * Initialize the service.
     */
    public function initialize(): void
    {
        $this->initialized = true;
    }

    /**
     * Check if service is initialized.
     */
    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    /**
     * Get example data.
     */
    public function getData(): array
    {
        return [
            'name' => 'Example Bundle',
            'version' => '1.0.0',
            'description' => 'Example bundle for Architect Framework',
            'features' => [
                'Service registration',
                'Configuration loading',
                'Route registration',
                'View templates',
                'Asset publishing',
                'Console commands',
                'Database migrations',
            ]
        ];
    }

    /**
     * Process data.
     */
    public function process(string $input): string
    {
        return strtoupper($input);
    }

    /**
     * Cleanup service resources.
     */
    public function cleanup(): void
    {
        $this->initialized = false;
    }
}