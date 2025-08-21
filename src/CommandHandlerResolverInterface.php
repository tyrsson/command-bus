<?php

declare(strict_types=1);

namespace PhpCmd\CmdBus;

interface CommandHandlerResolverInterface
{
    public function resolve(CommandInterface $command): CommandHandlerInterface;
}
