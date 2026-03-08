<?php

declare(strict_types=1);

namespace Webware\CommandBus\Container;

use Psr\Container\ContainerInterface;
use Webware\CommandBus\CommandHandlerResolver;
use Webware\CommandBus\CommandHandlerResolverInterface;

/**
 * @internal
 */
final readonly class CommandHandlerResolverFactory
{
    public function __invoke(ContainerInterface $container): CommandHandlerResolverInterface
    {
        return new CommandHandlerResolver($container);
    }
}
