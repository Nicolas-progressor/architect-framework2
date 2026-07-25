<?php

declare(strict_types=1);

namespace Architect\Core\Exception;

use Psr\Container\ContainerExceptionInterface;

class ContainerException extends \RuntimeException implements ContainerExceptionInterface {}
