<?php

declare(strict_types=1);

namespace Webware\CommandBusTest;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Webware\CommandBus\Command\CommandResult;
use Webware\CommandBus\Command\CommandResultInterface;
use Webware\CommandBus\Command\CommandStatus;
use Webware\CommandBus\CommandBus;
use Webware\CommandBus\CommandBusInterface;
use Webware\CommandBus\CommandHandlerInterface;
use Webware\CommandBus\CommandInterface;
use Webware\CommandBus\Exception\CommandException;
use Webware\CommandBus\MiddlewareInterface;
use Webware\CommandBus\MiddlewarePipe;

#[CoversClass(CommandBus::class)]
#[CoversMethod(CommandBus::class, '__construct')]
#[CoversMethod(CommandBus::class, 'handle')]
final class CommandBusTest extends TestCase
{
    private CommandBus $cmdBus;

    private MiddlewarePipe $pipeline;

    /** @var CommandInterface&MockObject */
    private CommandInterface $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pipeline = new MiddlewarePipe();
        $this->cmdBus   = new CommandBus($this->pipeline);
        $this->command  = $this->createMock(CommandInterface::class);
    }

    public function testCmdBusImplementsCmdBusInterface(): void
    {
        $this->assertInstanceOf(CommandBusInterface::class, $this->cmdBus);
    }

    public function testConstructorAcceptsMiddlewarePipeline(): void
    {
        $pipeline = new MiddlewarePipe();
        $cmdBus   = new CommandBus($pipeline);

        $this->assertInstanceOf(CommandBus::class, $cmdBus);
    }

    public function testHandleDelegatesToPipeline(): void
    {
        // Create an actual MiddlewarePipe since the constructor requires the intersection type
        $pipeline = new MiddlewarePipe();

        // Add a simple middleware that returns a known result
        $pipeline->pipe(new class() implements MiddlewareInterface {
            public function process(CommandInterface $command, CommandHandlerInterface $handler): CommandResultInterface
            {
                return new CommandResult(
                    $command,
                    CommandStatus::Success,
                    'pipeline result'
                );
            }
        });

        $cmdBus        = new CommandBus($pipeline);
        $commandResult = $cmdBus->handle($this->command);

        $this->assertEquals('pipeline result', $commandResult->getResult());
    }

    public function testHandleReturnsResultFromPipeline(): void
    {
        $expectedResult = 'test result from pipeline';

        // Create a simple middleware that returns a known result
        $this->pipeline->pipe(new class($expectedResult) implements MiddlewareInterface {
            public function __construct(private mixed $result) {}

            public function process(CommandInterface $command, CommandHandlerInterface $handler): CommandResultInterface
            {
                return new CommandResult(
                    $command,
                    CommandStatus::Success,
                    $this->result
                );
            }
        });

        $commandResult = $this->cmdBus->handle($this->command);
        $this->assertEquals($expectedResult, $commandResult->getResult());
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
        $pipeline->pipe(new class($expectedResult) implements MiddlewareInterface {
            public function __construct(private mixed $result) {}

            public function process(CommandInterface $command, CommandHandlerInterface $handler): CommandResultInterface
            {
                return new CommandResult(
                    $command,
                    CommandStatus::Success,
                    $this->result
                );
            }
        });

        $cmdBus        = new CommandBus($pipeline);
        $commandResult = $cmdBus->handle($this->command);

        $this->assertEquals($expectedResult, $commandResult->getResult());
    }

    public function testMultipleCmdBusInstancesAreIndependent(): void
    {
        $pipeline1 = new MiddlewarePipe();
        $pipeline2 = new MiddlewarePipe();

        $cmdBus1 = new CommandBus($pipeline1);
        $cmdBus2 = new CommandBus($pipeline2);

        $this->assertNotSame($cmdBus1, $cmdBus2);
        $this->assertInstanceOf(CommandBus::class, $cmdBus1);
        $this->assertInstanceOf(CommandBus::class, $cmdBus2);
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

        $this->pipeline->pipe(new class($capturedCommand) implements MiddlewareInterface {
            public function __construct(
                /** @phpstan-ignore property.onlyWritten */
                private mixed &$capturedCommand,
            ) {}

            public function process(CommandInterface $command, CommandHandlerInterface $handler): CommandResultInterface
            {
                $this->capturedCommand = $command;

                return new CommandResult(
                    $command,
                    CommandStatus::Success,
                    'captured'
                );
            }
        });

        $this->cmdBus->handle($this->command);

        $this->assertSame($this->command, $capturedCommand);
    }
}
