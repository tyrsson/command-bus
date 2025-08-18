<?php

declare(strict_types=1);

namespace PhpCmd\CmdBus;

interface CommandHandlerInterface
{
    public function handle(CommandInterface $command): mixed;
}
