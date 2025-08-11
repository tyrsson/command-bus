<?php

declare(strict_types=1);

namespace PhpCmd\Exception;

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
        return new self(sprintf('Invalid type for configuration key "%s": %s', $key, get_debug_type($value)));
    }
}
