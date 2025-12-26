<?php

declare(strict_types=1);

namespace Webware\CommandBus;

interface CommandHandlerResolverInterface
{
    public function resolve(CommandInterface $command): CommandHandlerInterface;
}
