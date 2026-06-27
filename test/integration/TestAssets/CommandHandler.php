<?php

declare(strict_types=1);

namespace Webware\CommandBusIntegrationTest\TestAssets;

use Webmozart\Assert\Assert;
use Webware\CommandBus\Command\CommandResult;
use Webware\CommandBus\Command\CommandResultInterface;
use Webware\CommandBus\Command\CommandStatus;
use Webware\CommandBus\CommandHandlerInterface;
use Webware\CommandBus\CommandInterface;

final class CommandHandler implements CommandHandlerInterface
{
    public function handle(CommandInterface $command): CommandResultInterface
    {
        Assert::isInstanceOf(
            $command,
            Command::class,
            'Expected instance of ' . Command::class,
        );

        return new CommandResult($command, CommandStatus::Success, $command->execute());
    }
}
