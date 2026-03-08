<?php

declare(strict_types=1);

namespace Webware\CommandBus\Container;

use Psr\Container\ContainerInterface;
use Webware\CommandBus\CommandHandlerResolverInterface;
use Webware\CommandBus\Middleware\CommandHandlerMiddleware;
use Webware\CommandBus\MiddlewareInterface;

/**
 * @internal
 */
final readonly class CommandHandlerMiddlewareFactory
{
    public function __invoke(
        ContainerInterface $container,
    ): MiddlewareInterface&CommandHandlerMiddleware {
        /** @var CommandHandlerResolverInterface $resolver */
        $resolver = $container->get(CommandHandlerResolverInterface::class);

        return new CommandHandlerMiddleware($resolver);
    }
}
