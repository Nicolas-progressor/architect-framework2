<?php

declare(strict_types=1);

/**
 * Architect Framework 2
 * Точка входа в приложение
 */

define('ROOT_DIR', dirname(__DIR__) . '/');
define('APP_DIR', ROOT_DIR . 'app/');
define('ARC_DIR', ROOT_DIR . 'architect/');
define('ROOT_URL', '/');

require_once ARC_DIR . 'bootstrap.php';
