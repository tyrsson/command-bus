<?php

declare(strict_types=1);

namespace Webware\CommandBus\Command;

enum CommandStatus implements CommandStatusInterface
{
    case Success;
    case Failure;
}
