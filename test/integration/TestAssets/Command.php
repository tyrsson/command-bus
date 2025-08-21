<?php

declare(strict_types=1);

namespace PhpCmd\CmdBusIntegrationTest\TestAssets;

use PhpCmd\CmdBus\Command\NamedCommandInterface;
use PhpCmd\CmdBus\Command\NamedCommandTrait;

final class Command implements NamedCommandInterface
{
    use NamedCommandTrait;

    public function __construct(
        string $name = 'Command-One'
    ) {
        $this->name = $name;
    }
}
