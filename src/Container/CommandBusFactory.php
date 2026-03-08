<?php

declare(strict_types=1);

namespace Webware\CommandBus\Container;

use Psr\Container\ContainerInterface;
use Webware\CommandBus\CommandBus;
use Webware\CommandBus\CommandBusInterface;
use Webware\CommandBus\Exception\ServiceNotFoundException;
use Webware\CommandBus\MiddlewarePipe;
use Webware\CommandBus\MiddlewarePipelineInterface;

/**
 * @internal
 */
final readonly class CommandBusFactory
{
    public function __invoke(ContainerInterface $container): CommandBusInterface
    {
        if (! $container->has(MiddlewarePipelineInterface::class)) {
            throw ServiceNotFoundException::fromService(MiddlewarePipelineInterface::class);
        }

        /** @var MiddlewarePipe&MiddlewarePipelineInterface $middlewarePipeline */
        $middlewarePipeline = $container->get(MiddlewarePipelineInterface::class);

        return new CommandBus($middlewarePipeline);
    }
}
