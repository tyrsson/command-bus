<?php

declare(strict_types=1);

namespace PhpCmd\CmdBus\Container;

use Assert\Assertion;
use PhpCmd\CmdBus\CommandHandlerResolverInterface;
use PhpCmd\CmdBus\Middleware\CommandHandlerMiddleware;
use Psr\Container\ContainerInterface;

final class CommandHandlerMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): CommandHandlerMiddleware
    {
        $resolver = $container->get(CommandHandlerResolverInterface::class);
        Assertion::isInstanceOf($resolver, CommandHandlerResolverInterface::class);
        return new CommandHandlerMiddleware($resolver);
    }
}
