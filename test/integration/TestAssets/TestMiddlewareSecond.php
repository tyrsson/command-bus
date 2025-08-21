<?php

declare(strict_types=1);

namespace PhpCmd\CmdBusIntegrationTest\TestAssets;

use PhpCmd\CmdBus\Command\CommandResult;
use PhpCmd\CmdBus\CommandHandlerInterface;
use PhpCmd\CmdBus\CommandInterface;
use PhpCmd\CmdBus\MiddlewareInterface;

final class TestMiddlewareSecond implements MiddlewareInterface
{
    public function process(
        CommandInterface|CommandResult $command,
        CommandHandlerInterface $handler
    ): mixed {
        // Custom processing logic for this middleware
        if ($command instanceof CommandResult) {
            // Handle the command result if needed
            return $command->getResult();
        }
        return $handler->handle($command);
    }
}
