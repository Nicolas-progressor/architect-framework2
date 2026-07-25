<?php

declare(strict_types=1);

namespace Architect\Core\Exception;

use Psr\Container\NotFoundExceptionInterface;

class NotFoundException extends \RuntimeException implements NotFoundExceptionInterface {}