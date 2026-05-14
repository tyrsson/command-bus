<?php

declare(strict_types=1);

namespace Webware\CommandBus\Command;

use Webware\CommandBus\CommandInterface;

interface CommandResultInterface extends CommandInterface
{
    public function getCommand(): CommandInterface;

    public function getStatus(): CommandStatusInterface;

    public function getResult(): mixed;
}
