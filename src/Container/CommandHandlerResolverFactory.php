<?php

declare(strict_types=1);

namespace PhpCmd\CmdBus\Container;

use PhpCmd\CmdBus\CommandHandlerResolver;
use Psr\Container\ContainerInterface;

final class CommandHandlerResolverFactory
{
    public function __invoke(ContainerInterface $container): CommandHandlerResolver
    {
        return new CommandHandlerResolver($container);
    }
}
