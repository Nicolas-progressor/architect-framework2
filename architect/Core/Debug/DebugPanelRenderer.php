<?php

declare(strict_types=1);

namespace Architect\Core\Debug;

use Architect\Core\Contracts\ContainerInterface;

/**
 * Renders debug panel at the end of the page.
 */
class DebugPanelRenderer
{
    public function __construct(
        private ContainerInterface $container
    ) {}

    public function render(): void
    {
        if ($this->shouldSkip()) {
            return;
        }

        try {
            $debug = $this->container->get('debug');
            $debug->render();
        } catch (\Exception $e) {
            // Silently ignore debug panel errors
        }
    }

    private function shouldSkip(): bool
    {
        // Check if it's an API request
        $requestDetector = new \Architect\Core\Http\RequestDetector();
        if ($requestDetector->isApiRequest()) {
            return true;
        }

        // Check if debug is enabled
        try {
            $debug = $this->container->get('debug');
            if (!$debug->isEnabled()) {
                $config = $this->container->get('config');
                $debugConfig = $config->get('debug', []);
                $env = $this->container->get('environment');

                $forceEnabled = ($debugConfig['enabled'] ?? false) && !$env->isProduction();
                if (!$forceEnabled) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            return true;
        }

        return false;
    }
}
