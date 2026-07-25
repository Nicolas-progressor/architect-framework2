<?php

declare(strict_types=1);

namespace Architect\Services\Mvc\Middleware\Contracts;

use Psr\Http\Server\MiddlewareInterface as PsrMiddlewareInterface;

/**
 * Middleware interface alias for PSR-15.
 * 
 * Uses standard PSR-15 MiddlewareInterface.
 * 
 * @package Architect\Services\Mvc\Middleware\Contracts
 */
interface MiddlewareInterface extends PsrMiddlewareInterface
{
}
