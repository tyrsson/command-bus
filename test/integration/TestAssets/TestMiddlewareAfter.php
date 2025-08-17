<?php

declare(strict_types=1);

namespace PhpCmdIntegrationTest\TestAssets;

use PhpCmd\CommandInterface;
use PhpCmd\CommandHandlerInterface;
use PhpCmd\MiddlewareInterface;

final class TestMiddlewareAfter implements MiddlewareInterface
{
    public function process(CommandInterface $command, CommandHandlerInterface $next): mixed
    {
        // Custom processing logic for this middleware
        $result = $next->handle($command);
        return $result;
    }
}
