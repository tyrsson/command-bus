<?php

declare(strict_types=1);

namespace PhpCmd;

final class CmdBus implements CmdBusInterface
{
    public function __construct(
        private MiddlewarePipelineInterface&MiddlewarePipe $pipeline
    ) {
    }

    public function handle(CommandInterface $command): mixed
    {
        return $this->pipeline->handle($command);
    }
}
