<?php

declare(strict_types=1);

namespace PhpCmd\CmdBus\Middleware;

use Override;
use PhpCmd\CmdBus\CommandHandlerFactory;
use PhpCmd\CmdBus\CommandHandlerInterface;
use PhpCmd\CmdBus\CommandInterface;
use PhpCmd\CmdBus\MiddlewareInterface;

final class CommandHandlerMiddleware implements MiddlewareInterface, CommandHandlerInterface
{
    private CommandHandlerInterface $handler;

    public function __construct(
        private readonly CommandHandlerFactory $factory
    ) {
    }

    #[Override]
    public function process(
        CommandInterface $command,
        CommandHandlerInterface $handler
    ): mixed {
        $this->handler = ($this->factory)($command);
        // run the command and return the result to the caller.
        return $this->handle($command);
    }

    #[Override]
    public function handle(CommandInterface $command): mixed
    {
        return $this->handler->handle($command);
    }
}
