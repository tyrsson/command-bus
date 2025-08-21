<?php

declare(strict_types=1);

namespace PhpCmd\CmdBus\Command;

enum CommandStatus
{
    case Success;
    case Failure;
}
