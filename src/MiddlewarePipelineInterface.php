<?php

declare(strict_types=1);

namespace PhpCmd\CmdBus;

interface MiddlewarePipelineInterface extends MiddlewareInterface, CommandHandlerInterface
{
    public function pipe(MiddlewareInterface $middleware): void;
}
