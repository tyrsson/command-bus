<?php

declare(strict_types=1);

namespace PhpCmd\CmdBus\Handler;

use PhpCmd\CmdBus\CommandHandlerInterface;
use PhpCmd\CmdBus\CommandInterface;
use PhpCmd\CmdBus\Exception\NoCommandHandledException;

final class EmptyPipelineHandler implements CommandHandlerInterface
{
    public function handle(CommandInterface $command): mixed
    {
        throw NoCommandHandledException::fromCommandClass($command::class);
    }
}
