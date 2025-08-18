<?php

declare(strict_types=1);

namespace PhpCmd\CmdBus;

interface MiddlewareInterface
{
    public function process(CommandInterface $command, CommandHandlerInterface $handler): mixed;
}
