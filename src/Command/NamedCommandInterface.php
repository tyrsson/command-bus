<?php

declare(strict_types=1);

namespace Webware\CommandBus\Command;

use Webware\CommandBus\CommandInterface;

interface NamedCommandInterface extends CommandInterface
{
    public function getName(): string;
}
