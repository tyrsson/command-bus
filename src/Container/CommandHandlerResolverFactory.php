<?php

declare(strict_types=1);

namespace Webware\CommandBus\Container;

use Psr\Container\ContainerInterface;
use Webware\CommandBus\CommandHandlerResolver;

final class CommandHandlerResolverFactory
{
    public function __invoke(ContainerInterface $container): CommandHandlerResolver
    {
        return new CommandHandlerResolver($container);
    }
}
