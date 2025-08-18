<?php

declare(strict_types=1);

namespace PhpCmd\CmdBus\Container;

use Assert\Assertion;
use PhpCmd\CmdBus\CommandHandlerFactory;
use PhpCmd\CmdBus\Middleware\CommandHandlerMiddleware;
use Psr\Container\ContainerInterface;

final class CommandHandlerMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): CommandHandlerMiddleware
    {
        $factory = $container->get(CommandHandlerFactory::class);
        Assertion::isInstanceOf($factory, CommandHandlerFactory::class);
        return new CommandHandlerMiddleware($factory);
    }
}
