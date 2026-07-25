<?php

declare(strict_types=1);

// This script is called by Composer to cache bundles.
// It should be run from the project root.

use Architect\Core\Bundle\BundleDiscovery;

if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(__DIR__, 3) . '/');
}

require_once ROOT_DIR . 'vendor/autoload.php';

try {
    BundleDiscovery::cache();
    fwrite(STDERR, "Cached bundles successfully.\n");
} catch (Throwable $e) {
    fwrite(STDERR, "Failed to cache bundles: " . $e->getMessage() . "\n");
    exit(1);
}