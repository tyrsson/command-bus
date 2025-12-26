<?php

declare(strict_types=1);

namespace Webware\CommandBus\Command;

use Override;

// @phpstan-ignore-next-line trait.unused
trait NamedCommandTrait
{
    protected readonly string $name;

    #[Override]
    public function getName(): string
    {
        return $this->name ?? static::class;
    }
}
