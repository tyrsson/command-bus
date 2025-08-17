<?php

declare(strict_types=1);

namespace PhpCmdIntegrationTest\TestAssets;

use PhpCmd\CommandInterface;
use PhpCmd\CommandHandlerInterface;
use PhpCmd\MiddlewareInterface;

final class TestMiddlewareBefore implements MiddlewareInterface
{
    public function process(CommandInterface $command, CommandHandlerInterface $next): mixed
    {
        // Custom processing logic for this middleware
        return $next->handle($command);
    }
}
