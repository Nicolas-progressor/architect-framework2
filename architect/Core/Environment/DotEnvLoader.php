<?php

declare(strict_types=1);

namespace Architect\Core\Environment;

/**
 * Loads environment variables from .env file.
 */
class DotEnvLoader
{
    public function load(string $envFile): void
    {
        if (!file_exists($envFile)) {
            return;
        }
        
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (empty($line) || $line[0] === '#') {
                continue;
            }
            
            $equalsPos = strpos($line, '=');
            if ($equalsPos === false) {
                continue;
            }
            
            $key = trim(substr($line, 0, $equalsPos));
            $value = trim(substr($line, $equalsPos + 1));
            
            // Remove quotes from value
            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }
            
            // Don't overwrite existing environment variables
            if (getenv($key) === false && !isset($_ENV[$key])) {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
            }
        }
    }
}