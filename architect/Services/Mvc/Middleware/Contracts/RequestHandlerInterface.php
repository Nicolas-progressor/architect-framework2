<?php

declare(strict_types=1);

namespace Architect\Services\Mvc\Middleware\Contracts;

use Psr\Http\Server\RequestHandlerInterface as PsrRequestHandlerInterface;

/**
 * Request handler interface alias for PSR-15.
 * 
 * Uses standard PSR-15 RequestHandlerInterface.
 * 
 * @package Architect\Services\Mvc\Middleware\Contracts
 */
interface RequestHandlerInterface extends PsrRequestHandlerInterface
{
}
