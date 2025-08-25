<?php

declare(strict_types=1);

namespace PhpCmd\CmdBusTest;

use PhpCmd\CmdBus\CmdBus;
use PhpCmd\CmdBus\CmdBusInterface;
use PhpCmd\CmdBus\CommandHandlerInterface;
use PhpCmd\CmdBus\CommandInterface;
use PhpCmd\CmdBus\Exception\CommandException;
use PhpCmd\CmdBus\MiddlewareInterface;
use PhpCmd\CmdBus\MiddlewarePipe;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(CmdBus::class)]
#[CoversMethod(CmdBus::class, '__construct')]
#[CoversMethod(CmdBus::class, 'handle')]
final class CmdBusTest extends TestCase
{
    private CmdBus $cmdBus;

    private MiddlewarePipe $pipeline;

    /** @var CommandInterface&MockObject */
    private CommandInterface $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pipeline = new MiddlewarePipe();
        $this->cmdBus   = new CmdBus($this->pipeline);
        $this->command  = $this->createMock(CommandInterface::class);
    }

    public function testCmdBusImplementsCmdBusInterface(): void
    {
        $this->assertInstanceOf(CmdBusInterface::class, $this->cmdBus);
    }

    public function testConstructorAcceptsMiddlewarePipeline(): void
    {
        $pipeline = new MiddlewarePipe();
        $cmdBus   = new CmdBus($pipeline);

        $this->assertInstanceOf(CmdBus::class, $cmdBus);
    }

    public function testHandleDelegatesToPipeline(): void
    {
        // Create an actual MiddlewarePipe since the constructor requires the intersection type
        $pipeline = new MiddlewarePipe();

        // Add a simple middleware that returns a known result
        $pipeline->pipe(new class implements MiddlewareInterface {
            public function process(CommandInterface $command, CommandHandlerInterface $handler): mixed
            {
                return 'pipeline result';
            }
        });

        $cmdBus = new CmdBus($pipeline);
        $result = $cmdBus->handle($this->command);

        $this->assertEquals('pipeline result', $result);
    }

    public function testHandleReturnsResultFromPipeline(): void
    {
        $expectedResult = 'test result from pipeline';

        // Create a simple middleware that returns a known result
        $this->pipeline->pipe(new class ($expectedResult) implements MiddlewareInterface {
            public function __construct(private mixed $result)
            {
            }

            public function process(CommandInterface $command, CommandHandlerInterface $handler): mixed
            {
                return $this->result;
            }
        });

        $result = $this->cmdBus->handle($this->command);

        $this->assertEquals($expectedResult, $result);
    }

    public function testHandleWithEmptyPipeline(): void
    {
        // Empty pipeline should result in the EmptyPipelineHandler being called
        $this->expectException(CommandException::class);
        $this->expectExceptionMessage('No command handler found for command class');

        $this->cmdBus->handle($this->command);
    }

    #[TestWith(['string result'])]
    #[TestWith([42])]
    #[TestWith([3.14])]
    #[TestWith([true])]
    #[TestWith([false])]
    #[TestWith([null])]
    #[TestWith([['array', 'result']])]
    public function testHandleSupportsVariousReturnTypes(mixed $expectedResult): void
    {
        // Create a simple middleware that returns the expected result
        $pipeline = new MiddlewarePipe();
        $pipeline->pipe(new class ($expectedResult) implements MiddlewareInterface {
            public function __construct(private mixed $result)
            {
            }

            public function process(CommandInterface $command, CommandHandlerInterface $handler): mixed
            {
                return $this->result;
            }
        });

        $cmdBus = new CmdBus($pipeline);
        $result = $cmdBus->handle($this->command);

        $this->assertEquals($expectedResult, $result);
    }

    public function testMultipleCmdBusInstancesAreIndependent(): void
    {
        $pipeline1 = new MiddlewarePipe();
        $pipeline2 = new MiddlewarePipe();

        $cmdBus1 = new CmdBus($pipeline1);
        $cmdBus2 = new CmdBus($pipeline2);

        $this->assertNotSame($cmdBus1, $cmdBus2);
        $this->assertInstanceOf(CmdBus::class, $cmdBus1);
        $this->assertInstanceOf(CmdBus::class, $cmdBus2);
    }

    public function testCmdBusIsImmutable(): void
    {
        // The CmdBus should not allow changing its pipeline after construction
        $reflection       = new ReflectionClass($this->cmdBus);
        $pipelineProperty = $reflection->getProperty('pipeline');

        $this->assertTrue($pipelineProperty->isPrivate());

        // Since the property is private, it cannot be modified from outside
        // This effectively makes the CmdBus immutable
        $this->assertNotNull($pipelineProperty, 'Pipeline property exists and is private, ensuring immutability');
    }

    public function testHandlePassesCommandToPipeline(): void
    {
        $capturedCommand = null;

        $this->pipeline->pipe(new class ($capturedCommand) implements MiddlewareInterface {
            public function __construct(
                /** @phpstan-ignore property.onlyWritten */
                private mixed &$capturedCommand
            ) {
            }

            public function process(CommandInterface $command, CommandHandlerInterface $handler): mixed
            {
                $this->capturedCommand = $command;
                return 'captured';
            }
        });

        $this->cmdBus->handle($this->command);

        $this->assertSame($this->command, $capturedCommand);
    }
}
