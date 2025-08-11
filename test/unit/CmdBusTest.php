<?php

declare(strict_types=1);

namespace PhpCmdTest;

use PhpCmd\CmdBus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[CoversClass(CmdBus::class)]
final class CmdBusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Set up any necessary dependencies or mocks here
    }

    #[CoversMethod('handle')]
    public function testHandle(): void
    {
        $cmdBus = new CmdBus();
        $result = $cmdBus->handle(new SomeCommand());
        $this->assertEquals('expected', $result);
    }
}
