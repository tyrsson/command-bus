<?php

declare(strict_types=1);

namespace Webware\CommandBus\Handler;

use Webware\CommandBus\Command\CommandResultInterface;
use Webware\CommandBus\CommandHandlerInterface;
use Webware\CommandBus\CommandInterface;
use Webware\CommandBus\Exception\CommandException;

/**
 * @internal
 */
final readonly class EmptyPipelineHandler implements CommandHandlerInterface
{
    public function handle(CommandInterface $command): CommandResultInterface
    {
        throw CommandException::commandNotHandled($command::class);
    }
}
