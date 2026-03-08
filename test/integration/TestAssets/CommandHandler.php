<?php

declare(strict_types=1);

namespace Webware\CommandBusIntegrationTest\TestAssets;

use Webware\CommandBus\Command\CommandResult;
use Webware\CommandBus\Command\CommandResultInterface;
use Webware\CommandBus\Command\CommandStatus;
use Webware\CommandBus\CommandHandlerInterface;
use Webware\CommandBus\CommandInterface;

use function assert;

final class CommandHandler implements CommandHandlerInterface
{
    public function handle(CommandInterface $command): CommandResultInterface
    {
        assert($command instanceof Command);

        return new CommandResult($command, CommandStatus::Success, $command->execute());
    }
}
