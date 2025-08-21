<?php

declare(strict_types=1);

namespace PhpCmd\CmdBus\Command;

use Override;

trait NamedCommandTrait
{
    protected readonly string $name;

    #[Override]
    public function getName(): string
    {
        return $this->name ?? static::class;
    }
}
