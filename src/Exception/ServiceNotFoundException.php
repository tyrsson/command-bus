<?php

declare(strict_types=1);

namespace PhpCmd\Exception;

use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

use function sprintf;

final class ServiceNotFoundException extends RuntimeException implements NotFoundExceptionInterface
{
    public static function fromService(string $serviceName): self
    {
        return new self(sprintf('Service not found: %s was not found in the container', $serviceName));
    }
}
