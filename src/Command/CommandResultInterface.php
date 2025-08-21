<?php

declare(strict_types=1);

namespace PhpCmd\CmdBus\Command;

use PhpCmd\CmdBus\CommandInterface;

interface CommandResultInterface extends CommandInterface
{
    public function getCommand(): CommandInterface;

    public function getStatus(): CommandStatus;

    public function getResult(): mixed;
}
