<?php

declare(strict_types=1);

namespace PhpCmd\Container;

use PhpCmd\Middleware\CommandHandlerMiddleware;
use Psr\Container\ContainerInterface;

final class CommandHandlerMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): CommandHandlerMiddleware
    {
        return new CommandHandlerMiddleware($container);
    }
}
