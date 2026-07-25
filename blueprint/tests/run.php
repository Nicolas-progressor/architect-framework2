#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Blueprint Test Runner Script
 * 
 * Usage: php blueprint/tests/run.php
 */

// Autoload - use project root vendor
$autoloadPaths = [
    __DIR__ . '/../../vendor/autoload.php',   // From blueprint/tests -> project root
];

$autoloadPath = null;
foreach ($autoloadPaths as $path) {
    if (file_exists($path)) {
        $autoloadPath = $path;
        break;
    }
}

if ($autoloadPath === null) {
    echo "Error: Composer autoload not found. Run 'composer install' first.\n";
    exit(1);
}

require_once $autoloadPath;

// Manually include test file
require_once __DIR__ . '/BlueprintTestRunner.php';

// Run tests
$runner = new \Blueprint\Tests\BlueprintTestRunner();
$runner->run();
