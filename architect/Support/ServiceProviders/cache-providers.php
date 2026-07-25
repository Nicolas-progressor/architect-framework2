<?php

declare(strict_types=1);

// This script is called by Composer to cache service providers.
// It should be run from the project root.

use Architect\Support\ServiceProviders\ProviderDiscovery;

if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(__DIR__, 3) . '/');
}

require_once ROOT_DIR . 'vendor/autoload.php';

try {
    ProviderDiscovery::cache();
    fwrite(STDERR, "Cached service providers successfully.\n");
} catch (Throwable $e) {
    fwrite(STDERR, "Failed to cache service providers: " . $e->getMessage() . "\n");
    exit(1);
}