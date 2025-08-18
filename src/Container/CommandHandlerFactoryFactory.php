<?php

declare(strict_types=1);

namespace PhpCmd\CmdBus\Container;

use PhpCmd\CmdBus\CommandHandlerFactory;
use Psr\Container\ContainerInterface;

final class CommandHandlerFactoryFactory
{
    public function __invoke(ContainerInterface $container): CommandHandlerFactory
    {
        return new CommandHandlerFactory($container);
    }
}
