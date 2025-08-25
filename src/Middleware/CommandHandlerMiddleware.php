<?php

declare(strict_types=1);

namespace PhpCmd\CmdBus\Middleware;

use Override;
use PhpCmd\CmdBus\Command\CommandResult;
use PhpCmd\CmdBus\Command\CommandStatus;
use PhpCmd\CmdBus\CommandHandlerInterface;
use PhpCmd\CmdBus\CommandHandlerResolverInterface;
use PhpCmd\CmdBus\CommandInterface;
use PhpCmd\CmdBus\MiddlewareInterface;
use Throwable;

class CommandHandlerMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly CommandHandlerResolverInterface $resolver
    ) {
    }

    #[Override]
    public function process(
        CommandInterface $command,
        CommandHandlerInterface $handler
    ): mixed {
        // Resolve the command handler for the given command
        $cmdHandler = $this->resolver->resolve($command);
        try {
            // run the command and capture results
            $result = $cmdHandler->handle($command);
            // create a new CommandResult with the captured results
            $command = new CommandResult($command, CommandStatus::Success, $result);
        } catch (Throwable $th) {
            $command = new CommandResult($command, CommandStatus::Failure, $th);
        }
        return $handler->handle($command);
    }
}
