<?php

declare(strict_types=1);

namespace PhpCmd\Container;

use PhpCmd\CmdBus;
use PhpCmd\Exception\ServiceNotFoundException;
use PhpCmd\MiddlewarePipe;
use PhpCmd\MiddlewarePipelineInterface;
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
