<?php

declare(strict_types=1);

namespace PhpCmd;

use Override;

final class CmdBus implements CmdBusInterface
{
    public function __construct(
        private MiddlewarePipelineInterface&MiddlewarePipe $pipeline
    ) {
    }

    #[Override]
    public function handle(CommandInterface $command): mixed
    {
        return $this->pipeline->handle($command);
    }
}
