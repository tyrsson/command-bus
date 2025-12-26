<?php

declare(strict_types=1);

namespace Webware\CommandBus\Container;

use Assert\Assertion;
use Psr\Container\ContainerInterface;
use Webware\CommandBus\CommandHandlerResolverInterface;
use Webware\CommandBus\Middleware\CommandHandlerMiddleware;

final class CommandHandlerMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): CommandHandlerMiddleware
    {
        $resolver = $container->get(CommandHandlerResolverInterface::class);
        Assertion::isInstanceOf($resolver, CommandHandlerResolverInterface::class);
        return new CommandHandlerMiddleware($resolver);
    }
}
