<?php

declare(strict_types=1);

namespace PhpCmd\CmdBusIntegrationTest\TestAssets;

use PhpCmd\CmdBus\CommandHandlerInterface;
use PhpCmd\CmdBus\CommandInterface;
use PhpCmd\CmdBus\MiddlewareInterface;

final class TestMiddlewareSecond implements MiddlewareInterface
{
    public function process(
        CommandInterface $command,
        CommandHandlerInterface $handler
    ): mixed {
        // Custom processing logic for this middleware
        return $handler->handle($command);
    }
}
