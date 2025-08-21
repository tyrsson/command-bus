<?php

declare(strict_types=1);

namespace PhpCmd\CmdBus\Command;

use Override;
use PhpCmd\CmdBus\CommandInterface;

class CommandResult implements CommandResultInterface
{
    public function __construct(
        private CommandInterface $command,
        private CommandStatus $status,
        private mixed $result
    ) {
    }

    #[Override]
    public function getCommand(): CommandInterface
    {
        return $this->command;
    }

    #[Override]
    public function getStatus(): CommandStatus
    {
        return $this->status;
    }

    #[Override]
    public function getResult(): mixed
    {
        return $this->result;
    }
}
