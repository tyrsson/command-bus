<?php

declare(strict_types=1);

namespace Webware\CommandBusIntegrationTest\TestAssets;

use Webware\CommandBus\Command\CommandResultInterface;
use Webware\CommandBus\CommandHandlerInterface;
use Webware\CommandBus\CommandInterface;
use Webware\CommandBus\MiddlewareInterface;

final class TestMiddlewareSecond implements MiddlewareInterface
{
    public function process(
        CommandInterface $command,
        CommandHandlerInterface $handler
    ): CommandResultInterface {
        // Custom processing logic for this middleware
        return $handler->handle($command);
    }
}
