<?php

declare(strict_types=1);

namespace PhpCmd\CmdBus\Command;

use Override;

// @phpstan-ignore trait.unused
trait NamedCommandTrait
{
    protected readonly string $name;

    #[Override]
    public function getName(): string
    {
        return $this->name ?? static::class;
    }
}
