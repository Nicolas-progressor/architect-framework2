<?php

declare(strict_types=1);

/**
 * Console Services Index
 *
 * This file provides convenient access to console commands and utilities.
 * Note: Use autoloading via Composer instead of manual requires.
 */

namespace Architect\Console;

// Re-export main classes with shorter aliases for convenience
// These are NOT class_alias - they are proper use statements at file level
// Use: use Architect\Console\ConsoleKernel as Kernel;

return [
    'kernel' => ConsoleKernel::class,
    'command' => BaseCommand::class,
    'command_interface' => CommandInterface::class,
    'input' => Input::class,
    'output' => OutputFormatter::class,
    'registry' => CommandRegistry::class,
];
