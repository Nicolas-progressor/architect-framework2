<?php

declare(strict_types=1);

namespace Architect\Core\Environment;

/**
 * Detects application environment from various sources.
 */
class EnvDetector implements EnvDetectorInterface
{
    private const ENVIRONMENTS = ['development', 'testing', 'staging', 'production'];
    private const DEFAULT_ENVIRONMENT = 'production';

    public function detect(): string
    {
        // 1. OS environment variable has highest priority
        $env = getenv('APP_ENV');
        if ($env !== false && $this->isValidEnvironment($env)) {
            return $env;
        }

        // 2. Check PHP constant APP_ENV
        if (defined('APP_ENV') && $this->isValidEnvironment(APP_ENV)) {
            return APP_ENV;
        }

        // 3. .env file in project root
        $envFile = ROOT_DIR . '.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);

                if (empty($line) || $line[0] === '#') {
                    continue;
                }

                if (str_starts_with($line, 'APP_ENV=')) {
                    $value = trim(substr($line, 8));
                    if ($this->isValidEnvironment($value)) {
                        putenv("APP_ENV={$value}");
                        $_ENV['APP_ENV'] = $value;
                        return $value;
                    }
                }
            }
        }

        // 4. Default value
        return self::DEFAULT_ENVIRONMENT;
    }

    private function isValidEnvironment(string $env): bool
    {
        return in_array(strtolower($env), self::ENVIRONMENTS, true);
    }
}
