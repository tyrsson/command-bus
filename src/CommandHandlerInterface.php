<?php

declare(strict_types=1);

namespace PhpCmd;

interface CommandHandlerInterface
{
    public function handle(CommandInterface $command): mixed;
}
