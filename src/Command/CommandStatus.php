<?php

declare(strict_types=1);

namespace Webware\CommandBus\Command;

enum CommandStatus
{
    case Success;
    case Failure;
}
