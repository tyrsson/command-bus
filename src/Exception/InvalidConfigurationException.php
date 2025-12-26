<?php

declare(strict_types=1);

namespace Webware\CommandBus\Exception;

use InvalidArgumentException;

use function get_debug_type;
use function sprintf;

final class InvalidConfigurationException extends InvalidArgumentException
{
    public static function fromMissingKey(string $key): self
    {
        return new self(sprintf('Configuration key "%s" is missing.', $key));
    }

    public static function fromInvalidValue(string $key, mixed $value): self
    {
        return new self(sprintf('Invalid value for configuration key "%s": %s', $key, get_debug_type($value)));
    }

    public static function fromInvalidType(string $key, mixed $value): self
    {
        return new self(sprintf('Invalid type for config key "%s": %s', $key, get_debug_type($value)));
    }

    public static function fromUnMappedCommand(string $commandClass): self
    {
        return new self(sprintf('Missing CommandMap entry for "%s".', $commandClass));
    }

    public static function fromInvalidHandler(string $handlerClass, mixed $handler): self
    {
        return new self(sprintf(
            'Invalid command handler for "%s". Expected instance of CommandHandlerInterface, got %s.',
            $handlerClass,
            get_debug_type($handler)
        ));
    }

    public static function fromHandlerNotFound(string $handlerClass): ServiceNotFoundException
    {
        return ServiceNotFoundException::fromService($handlerClass);
    }
}
