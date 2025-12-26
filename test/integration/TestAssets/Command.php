<?php

declare(strict_types=1);

namespace Webware\CommandBusIntegrationTest\TestAssets;

use Webware\CommandBus\CommandInterface;

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
