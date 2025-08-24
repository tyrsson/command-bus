<?php

declare(strict_types=1);

namespace PhpCmd\CmdBus\Middleware;

use Override;
use PhpCmd\CmdBus\CommandHandlerInterface;
use PhpCmd\CmdBus\CommandInterface;
use PhpCmd\CmdBus\MiddlewareInterface;

class PreCommandHandlerMiddleware implements MiddlewareInterface
{
    #[Override]
    public function process(CommandInterface $command, CommandHandlerInterface $handler): mixed
    {
        // Pre-processing logic here
        return $handler->handle($command);
    }
}
