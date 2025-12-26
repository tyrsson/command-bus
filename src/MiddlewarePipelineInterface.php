<?php

declare(strict_types=1);

namespace Webware\CommandBus;

interface MiddlewarePipelineInterface extends MiddlewareInterface, CommandHandlerInterface
{
    public function pipe(MiddlewareInterface $middleware): void;
}
