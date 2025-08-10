<?php

declare(strict_types=1);

namespace PhpCmd;

final class CmdBus implements CmdBusInterface
{
    private MiddlewarePipelineInterface $pipeline;

    public function __construct(MiddlewarePipelineInterface $pipeline)
    {
        $this->pipeline = $pipeline;
    }

    public function process(CommandInterface $command): mixed
    {

    }
}
