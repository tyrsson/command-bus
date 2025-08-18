<?php

declare(strict_types=1);

namespace PhpCmd\CmdBusTest;

use PhpCmd\CmdBus\CmdBus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CmdBus::class)]
final class CmdBusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testBus(): void
    {
        $this->markTestIncomplete('Not implemented yet');
    }
}
