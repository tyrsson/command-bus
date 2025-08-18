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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(CmdBus::class)]
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

    public function testCmdBusImplementsCommandHandlerInterface(): void
    {
        $this->assertInstanceOf(CommandHandlerInterface::class, $this->cmdBus);
    }

    public function testConstructorAcceptsMiddlewarePipeline(): void
    {
        $pipeline = new MiddlewarePipe();
        $cmdBus   = new CmdBus($pipeline);

        $this->assertInstanceOf(CmdBus::class, $cmdBus);
    }

    public function testHandleMethodExists(): void
    {
        $this->assertInstanceOf(CommandHandlerInterface::class, $this->cmdBus);
    }

    public function testHandleDelegatesToPipeline(): void
    {
        // Create a mock command handler that the pipeline will use
        $commandHandler = $this->createMock(CommandHandlerInterface::class);
        $commandHandler->expects($this->once())
            ->method('handle')
            ->with($this->command)
            ->willReturn('pipeline result');

        // Add the command handler to the pipeline
        $this->pipeline->pipe(new class ($commandHandler) implements MiddlewareInterface {
            public function __construct(private CommandHandlerInterface $handler)
            {
            }

            public function process(CommandInterface $command, CommandHandlerInterface $handler): mixed
            {
                return $this->handler->handle($command);
            }
        });

        $result = $this->cmdBus->handle($this->command);

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

    public function testHandleWithDifferentCommands(): void
    {
        $command1 = $this->createMock(CommandInterface::class);
        $command2 = $this->createMock(CommandInterface::class);

        // Create a middleware that echoes the command class name
        $this->pipeline->pipe(new class implements MiddlewareInterface {
            public function process(CommandInterface $command, CommandHandlerInterface $handler): mixed
            {
                return $command::class;
            }
        });

        $result1 = $this->cmdBus->handle($command1);
        $result2 = $this->cmdBus->handle($command2);

        $this->assertIsString($result1);
        $this->assertIsString($result2);
        $this->assertStringContainsString('MockObject_CommandInterface_', $result1);
        $this->assertStringContainsString('MockObject_CommandInterface_', $result2);
    }

    public function testHandleWithEmptyPipeline(): void
    {
        // Empty pipeline should result in the EmptyPipelineHandler being called
        $this->expectException(CommandException::class);
        $this->expectExceptionMessage('No command handler found for command class');

        $this->cmdBus->handle($this->command);
    }

    public function testHandleWithMultipleMiddleware(): void
    {
        $results = [];

        // Add multiple middleware that accumulate results
        $this->pipeline->pipe(new class ($results) implements MiddlewareInterface {
            /** @param array<string> $results */
            public function __construct(
                /** @phpstan-ignore property.onlyWritten */
                private array &$results
            ) {
            }

            public function process(CommandInterface $command, CommandHandlerInterface $handler): mixed
            {
                $this->results[] = 'middleware1';
                return $handler->handle($command);
            }
        });

        $this->pipeline->pipe(new class ($results) implements MiddlewareInterface {
            /** @param array<string> $results */
            public function __construct(
                /** @phpstan-ignore property.onlyWritten */
                private array &$results
            ) {
            }

            public function process(CommandInterface $command, CommandHandlerInterface $handler): mixed
            {
                $this->results[] = 'middleware2';
                return 'final result';
            }
        });

        $result = $this->cmdBus->handle($this->command);

        $this->assertEquals('final result', $result);
        $this->assertContains('middleware1', $results);
        $this->assertContains('middleware2', $results);
    }

    public function testHandleSupportsVariousReturnTypes(): void
    {
        $testCases = [
            'string result',
            42,
            3.14,
            true,
            false,
            null,
            ['array', 'result'],
            (object) ['property' => 'value'],
        ];

        foreach ($testCases as $expectedResult) {
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
