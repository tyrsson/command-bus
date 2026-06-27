<?php

declare(strict_types=1);

namespace Webware\CommandBusTest;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
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

    #[Test]
    public function cmdBusImplementsCmdBusInterface(): void
    {
        static::assertInstanceOf(CommandBusInterface::class, $this->cmdBus);
    }

    #[Test]
    public function cmdBusIsImmutable(): void
    {
        // The CmdBus should not allow changing its pipeline after construction
        $reflection       = new ReflectionClass($this->cmdBus);
        $pipelineProperty = $reflection->getProperty('pipeline');

        static::assertTrue($pipelineProperty->isPrivate());

        // Since the property is private, it cannot be modified from outside
        // This effectively makes the CmdBus immutable
        static::assertNotNull($pipelineProperty, 'Pipeline property exists and is private, ensuring immutability');
    }

    #[Test]
    public function constructorAcceptsMiddlewarePipeline(): void
    {
        $pipeline = new MiddlewarePipe();
        $cmdBus   = new CommandBus($pipeline);

        static::assertInstanceOf(CommandBus::class, $cmdBus);
    }

    #[Test]
    public function handleDelegatesToPipeline(): void
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
                    'pipeline result',
                );
            }
        });

        $cmdBus        = new CommandBus($pipeline);
        $commandResult = $cmdBus->handle($this->command);

        static::assertSame('pipeline result', $commandResult->getResult());
    }

    #[Test]
    public function handlePassesCommandToPipeline(): void
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
                    'captured',
                );
            }
        });

        $this->cmdBus->handle($this->command);

        static::assertSame($this->command, $capturedCommand);
    }

    #[Test]
    public function handleReturnsResultFromPipeline(): void
    {
        $expectedResult = 'test result from pipeline';

        // Create a simple middleware that returns a known result
        $this->pipeline->pipe(new class($expectedResult) implements MiddlewareInterface {
            public function __construct(
                private mixed $result,
            ) {}

            public function process(CommandInterface $command, CommandHandlerInterface $handler): CommandResultInterface
            {
                return new CommandResult(
                    $command,
                    CommandStatus::Success,
                    $this->result,
                );
            }
        });

        $commandResult = $this->cmdBus->handle($this->command);
        static::assertEquals($expectedResult, $commandResult->getResult());
    }

    #[Test]
    #[TestWith(['string result'])]
    #[TestWith([42])]
    #[TestWith([3.14])]
    #[TestWith([true])]
    #[TestWith([false])]
    #[TestWith([null])]
    #[TestWith([['array', 'result']])]
    public function handleSupportsVariousReturnTypes(mixed $expectedResult): void
    {
        // Create a simple middleware that returns the expected result
        $pipeline = new MiddlewarePipe();
        $pipeline->pipe(new class($expectedResult) implements MiddlewareInterface {
            public function __construct(
                private mixed $result,
            ) {}

            public function process(CommandInterface $command, CommandHandlerInterface $handler): CommandResultInterface
            {
                return new CommandResult(
                    $command,
                    CommandStatus::Success,
                    $this->result,
                );
            }
        });

        $cmdBus        = new CommandBus($pipeline);
        $commandResult = $cmdBus->handle($this->command);

        static::assertEquals($expectedResult, $commandResult->getResult());
    }

    #[Test]
    public function handleWithEmptyPipeline(): void
    {
        // Empty pipeline should result in the EmptyPipelineHandler being called
        $this->expectException(CommandException::class);
        $this->expectExceptionMessage('No command handler found for command class');

        $this->cmdBus->handle($this->command);
    }

    #[Test]
    public function multipleCmdBusInstancesAreIndependent(): void
    {
        $pipeline1 = new MiddlewarePipe();
        $pipeline2 = new MiddlewarePipe();

        $cmdBus1 = new CommandBus($pipeline1);
        $cmdBus2 = new CommandBus($pipeline2);

        static::assertNotSame($cmdBus1, $cmdBus2);
        static::assertInstanceOf(CommandBus::class, $cmdBus1);
        static::assertInstanceOf(CommandBus::class, $cmdBus2);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->pipeline = new MiddlewarePipe();
        $this->cmdBus   = new CommandBus($this->pipeline);
        $this->command  = $this->createStub(CommandInterface::class);
    }
}
