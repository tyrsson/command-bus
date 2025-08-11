<?php

declare(strict_types=1);

namespace PhpCmdIntegrationTest\TestAssets;

use PhpCmd\CommandHandlerInterface;
use PhpCmd\CommandInterface;

final class CommandHandler implements CommandHandlerInterface
{
    public function handle(CommandInterface $command): mixed
    {
        return $command->execute();
    }
}
