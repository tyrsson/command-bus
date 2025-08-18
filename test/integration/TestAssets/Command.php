<?php

declare(strict_types=1);

namespace PhpCmd\CmdBusIntegrationTest\TestAssets;

use PhpCmd\CmdBus\CommandInterface;

final class Command implements CommandInterface
{
    public function __construct(
        public string $name = 'Command-One'
    ) {
    }

    public function execute(): mixed
    {
        return $this->name;
    }
}
