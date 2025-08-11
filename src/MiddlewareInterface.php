<?php

declare(strict_types=1);

namespace PhpCmd;

interface MiddlewareInterface
{
    public function process(CommandInterface $command): mixed;
}
