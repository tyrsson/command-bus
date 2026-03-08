<?php

declare(strict_types=1);

namespace Webware\CommandBus;

use Override;

final readonly class CommandBus implements CommandBusInterface
{
    public function __construct(
        private MiddlewarePipelineInterface&MiddlewarePipe $pipeline,
    ) {}

    #[Override]
    public function handle(CommandInterface $command): Command\CommandResultInterface
    {
        return $this->pipeline->handle($command);
    }
}
