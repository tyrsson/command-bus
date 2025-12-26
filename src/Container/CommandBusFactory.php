<?php

declare(strict_types=1);

namespace Webware\CommandBus\Container;

use Psr\Container\ContainerInterface;
use Webware\CommandBus\CommandBus;
use Webware\CommandBus\Exception\ServiceNotFoundException;
use Webware\CommandBus\MiddlewarePipe;
use Webware\CommandBus\MiddlewarePipelineInterface;

final class CommandBusFactory
{
    public function __invoke(ContainerInterface $container): CommandBus
    {
        if (! $container->has(MiddlewarePipelineInterface::class)) {
            throw ServiceNotFoundException::fromService(MiddlewarePipelineInterface::class);
        }
        /** @var MiddlewarePipelineInterface&MiddlewarePipe $middlewarePipeline */
        $middlewarePipeline = $container->get(MiddlewarePipelineInterface::class);
        return new CommandBus($middlewarePipeline);
    }
}
