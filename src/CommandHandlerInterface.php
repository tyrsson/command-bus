<?php

declare(strict_types=1);

namespace Webware\CommandBus;

interface CommandHandlerInterface
{
    public function handle(CommandInterface $command): Command\CommandResultInterface;
}
