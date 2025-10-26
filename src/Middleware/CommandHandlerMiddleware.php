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

/** @internal */
final readonly class CommandHandlerMiddleware implements MiddlewareInterface
{
    public function __construct(
        private CommandHandlerResolverInterface $resolver
    ) {
    }

    #[Override]
    public function process(
        CommandInterface $command,
        CommandHandlerInterface $handler
    ): mixed {
        // Resolve the command handler for the given command
        $cmdHandler = $this->resolver->resolve($command);

        // create a new CommandResult with the captured results
        $command = new CommandResult(
            $command,
            CommandStatus::Success,
            $cmdHandler->handle($command)
        );

        return $handler->handle($command);
    }
}
