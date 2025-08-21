<?php

declare(strict_types=1);

namespace PhpCmd\CmdBusIntegrationTest\TestAssets;

use PhpCmd\CmdBus\CommandHandlerInterface;
use PhpCmd\CmdBus\CommandInterface;

use function assert;

final class CommandHandler implements CommandHandlerInterface
{
    public function handle(CommandInterface $command): mixed
    {
        assert($command instanceof Command);
        return $command->execute();
    }
}
