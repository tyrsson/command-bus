<?php

declare(strict_types=1);

namespace PhpCmd\CmdBus\Command;

use PhpCmd\CmdBus\CommandInterface;

interface NamedCommandInterface extends CommandInterface
{
    public function getName(): string;
}
