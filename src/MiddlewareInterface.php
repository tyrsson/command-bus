<?php

declare(strict_types=1);

namespace Webware\CommandBus;

interface MiddlewareInterface
{
    public function process(
        CommandInterface $command,
        CommandHandlerInterface $handler
    ): Command\CommandResultInterface;
}
