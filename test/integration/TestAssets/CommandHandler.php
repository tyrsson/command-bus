<?php

declare(strict_types=1);

namespace PhpCmd\CmdBusIntegrationTest\TestAssets;

use PhpCmd\CmdBus\CommandHandlerInterface;
use PhpCmd\CmdBus\CommandInterface;

final class CommandHandler implements CommandHandlerInterface
{
    public function handle(CommandInterface $command): mixed
    {
        return $command->execute();
    }
}
