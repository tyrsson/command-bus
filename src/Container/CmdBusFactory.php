<?php

declare(strict_types=1);

namespace PhpCmd\CmdBus\Container;

use PhpCmd\CmdBus\CmdBus;
use PhpCmd\CmdBus\Exception\ServiceNotFoundException;
use PhpCmd\CmdBus\MiddlewarePipe;
use PhpCmd\CmdBus\MiddlewarePipelineInterface;
use Psr\Container\ContainerInterface;

final class CmdBusFactory
{
    public function __invoke(ContainerInterface $container): CmdBus
    {
        if (! $container->has(MiddlewarePipelineInterface::class)) {
            throw ServiceNotFoundException::fromService(MiddlewarePipelineInterface::class);
        }
        /** @var MiddlewarePipelineInterface&MiddlewarePipe $middlewarePipeline */
        $middlewarePipeline = $container->get(MiddlewarePipelineInterface::class);
        return new CmdBus($middlewarePipeline);
    }
}
