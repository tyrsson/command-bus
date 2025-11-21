<?php

declare(strict_types=1);

namespace PhpCmd\CmdBusIntegrationTest\TestAssets;

use PhpCmd\CmdBus\Command\CommandResult;
use PhpCmd\CmdBus\Command\CommandResultInterface;
use PhpCmd\CmdBus\Command\CommandStatus;
use PhpCmd\CmdBus\CommandHandlerInterface;
use PhpCmd\CmdBus\CommandInterface;

use function assert;

final class CommandHandler implements CommandHandlerInterface
{
    public function handle(CommandInterface $command): CommandResultInterface
    {
        assert($command instanceof Command);
        return new CommandResult($command, CommandStatus::Success, $command->execute());
    }
}
