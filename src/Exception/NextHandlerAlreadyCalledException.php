<?php

declare(strict_types=1);

namespace Webware\CommandBus\Exception;

use DomainException;

final class NextHandlerAlreadyCalledException extends DomainException
{
    public static function create(): self
    {
        return new self('The next handler has already been called.');
    }
}
