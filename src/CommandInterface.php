<?php

declare(strict_types=1);

namespace PhpCmd\CmdBus;

interface CommandInterface
{
    public function execute(): mixed;
}
