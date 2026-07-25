#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Architect Console Entry Point
 *
 * Usage:
 *   php arc <command> [arguments] [options]
 *
 * Examples:
 *   php arc list
 *   php arc make:controller UserController
 *   php arc db:migrate --force
 *   php arc cache:clear
 */

define('ROOT_DIR', __DIR__ . '/');
define('APP_DIR', ROOT_DIR . 'app/');
define('ARC_DIR', ROOT_DIR . 'architect/');
define('ARC_LANG', 'ru');

// Set environment
putenv('APP_ENV=development');

// Autoload - try Composer first, then fallback to simple autoloader
if (file_exists(ROOT_DIR . 'vendor/autoload.php')) {
    require_once ROOT_DIR . 'vendor/autoload.php';
} else {
    // Simple autoloader for Console namespace
    $consoleAutoloader = function (string $class): void {
        $prefix = 'Architect\\Console\\';
        $baseDir = ARC_DIR . 'Services/Console/';

        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) {
            require $file;
        }
    };

    spl_autoload_register($consoleAutoloader);
    spl_autoload_register($consoleAutoloader, true, true);
}

// Create console kernel
$console = new \Architect\Console\ConsoleKernel();

// Register built-in commands
$console
    // Info commands
    ->registerCommand(new \Architect\Console\Commands\ArcInfoCommand())

    // Make commands
    ->registerCommand(new \Architect\Console\Commands\MakeAppCommand())
    ->registerCommand(new \Architect\Console\Commands\MakeModuleCommand())
    ->registerCommand(new \Architect\Console\Commands\MakeControllerCommand())
    ->registerCommand(new \Architect\Console\Commands\MakeModelCommand())
    ->registerCommand(new \Architect\Console\Commands\MakeViewCommand())
    ->registerCommand(new \Architect\Console\Commands\MakeRouteCommand())
    ->registerCommand(new \Architect\Console\Commands\MakeMigrationCommand())

    // Database commands
    ->registerCommand(new \Architect\Console\Commands\DbMigrateCommand())
    ->registerCommand(new \Architect\Console\Commands\DbRollbackCommand())
    ->registerCommand(new \Architect\Console\Commands\DbSeedCommand())

    // Cache commands
    ->registerCommand(new \Architect\Console\Commands\CacheClearCommand())
    ->registerCommand(new \Architect\Console\Commands\ConfigCacheCommand())

    // Optimization commands
    ->registerCommand(new \Architect\Console\Commands\OptimizeAutoloadCommand())

    // Route commands
    ->registerCommand(new \Architect\Console\Commands\RouteListCommand())

    // Test commands
    ->registerCommand(new \Architect\Console\Commands\TestRunCommand());

// Run the console
$exitCode = $console->run();

// Exit with proper code
exit($exitCode);
