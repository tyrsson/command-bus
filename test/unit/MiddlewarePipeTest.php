<?php

declare(strict_types=1);

namespace PhpCmd\CmdBusTest;

use PhpCmd\CmdBus\Command\CommandResult;
use PhpCmd\CmdBus\Command\CommandResultInterface;
use PhpCmd\CmdBus\Command\CommandStatus;
use PhpCmd\CmdBus\CommandHandlerInterface;
use PhpCmd\CmdBus\CommandInterface;
use PhpCmd\CmdBus\Exception\CommandException;
use PhpCmd\CmdBus\MiddlewareInterface;
use PhpCmd\CmdBus\MiddlewarePipe;
use PhpCmd\CmdBus\MiddlewarePipelineInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SplQueue;

#[CoversClass(MiddlewarePipe::class)]
final class MiddlewarePipeTest extends TestCase
{
    private MiddlewarePipe $middlewarePipe;

    /** @var CommandInterface&MockObject */
    private CommandInterface $command;

    /** @var CommandHandlerInterface&MockObject */
    private CommandHandlerInterface $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->middlewarePipe = new MiddlewarePipe();
        $this->command        = $this->createMock(CommandInterface::class);
        $this->handler        = $this->createMock(CommandHandlerInterface::class);
    }

    public function testMiddlewarePipeImplementsCorrectInterfaces(): void
    {
        $this->assertInstanceOf(MiddlewarePipelineInterface::class, $this->middlewarePipe);
        $this->assertInstanceOf(MiddlewareInterface::class, $this->middlewarePipe);
        $this->assertInstanceOf(CommandHandlerInterface::class, $this->middlewarePipe);
    }

    public function testConstructorInitializesEmptyPipeline(): void
    {
        $reflection = new ReflectionClass($this->middlewarePipe);
        $property   = $reflection->getProperty('pipeline');

        /** @var SplQueue<MiddlewareInterface> $pipeline */
        $pipeline = $property->getValue($this->middlewarePipe);

        $this->assertInstanceOf(SplQueue::class, $pipeline);
        $this->assertTrue($pipeline->isEmpty());
    }

    public function testPipeMethodExists(): void
    {
        $this->assertInstanceOf(MiddlewarePipelineInterface::class, $this->middlewarePipe);
    }

    public function testHandleMethodExists(): void
    {
        $this->assertInstanceOf(CommandHandlerInterface::class, $this->middlewarePipe);
    }

    public function testProcessMethodExists(): void
    {
        $this->assertInstanceOf(MiddlewareInterface::class, $this->middlewarePipe);
    }

    public function testPipeAddsMiddlewareToQueue(): void
    {
        $middleware = $this->createMock(MiddlewareInterface::class);

        $this->middlewarePipe->pipe($middleware);

        $reflection = new ReflectionClass($this->middlewarePipe);
        $property   = $reflection->getProperty('pipeline');

        /** @var SplQueue<MiddlewareInterface> $pipeline */
        $pipeline = $property->getValue($this->middlewarePipe);

        $this->assertFalse($pipeline->isEmpty());
        $this->assertCount(1, $pipeline);
    }

    public function testPipeAddsMultipleMiddleware(): void
    {
        $middleware1 = $this->createMock(MiddlewareInterface::class);
        $middleware2 = $this->createMock(MiddlewareInterface::class);
        $middleware3 = $this->createMock(MiddlewareInterface::class);

        $this->middlewarePipe->pipe($middleware1);
        $this->middlewarePipe->pipe($middleware2);
        $this->middlewarePipe->pipe($middleware3);

        $reflection = new ReflectionClass($this->middlewarePipe);
        $property   = $reflection->getProperty('pipeline');

        /** @var SplQueue<MiddlewareInterface> $pipeline */
        $pipeline = $property->getValue($this->middlewarePipe);

        $this->assertCount(3, $pipeline);
    }

    public function testHandleWithEmptyPipelineCallsEmptyPipelineHandler(): void
    {
        $this->expectException(CommandException::class);

        $this->middlewarePipe->handle($this->command);
    }

    public function testHandleWithMiddlewareCallsMiddleware(): void
    {
        $expectedResult = new CommandResult($this->command, CommandStatus::Success, 'middleware result');

        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->expects($this->once())
            ->method('process')
            ->with(
                $this->command,
                $this->isInstanceOf(CommandHandlerInterface::class)
            )
            ->willReturn($expectedResult);

        $this->middlewarePipe->pipe($middleware);

        $result = $this->middlewarePipe->handle($this->command);

        $this->assertSame($expectedResult, $result);
    }

    public function testProcessWithEmptyPipelineCallsHandler(): void
    {
        $expectedResult = new CommandResult($this->command, CommandStatus::Success, 'handler result');

        $this->handler->expects($this->once())
            ->method('handle')
            ->with($this->command)
            ->willReturn($expectedResult);

        $result = $this->middlewarePipe->process($this->command, $this->handler);

        $this->assertSame($expectedResult, $result);
    }

    public function testProcessWithMiddlewareCallsMiddleware(): void
    {
        $expectedResult = new CommandResult($this->command, CommandStatus::Success, 'middleware result');

        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->expects($this->once())
            ->method('process')
            ->with(
                $this->command,
                $this->isInstanceOf(CommandHandlerInterface::class)
            )
            ->willReturn($expectedResult);

        $this->middlewarePipe->pipe($middleware);

        $result = $this->middlewarePipe->process($this->command, $this->handler);

        $this->assertSame($expectedResult, $result);
    }

    public function testProcessWithMultipleMiddlewareCallsInOrder(): void
    {
        $executionOrder = [];

        $middleware1 = new class ($executionOrder) implements MiddlewareInterface {
            /** @param array<string> $executionOrder */
            public function __construct(
                /** @phpstan-ignore property.onlyWritten */
                private array &$executionOrder
            ) {
            }

            public function process(CommandInterface $command, CommandHandlerInterface $handler): CommandResultInterface
            {
                $this->executionOrder[] = 'middleware1';
                return $handler->handle($command);
            }
        };

        $middleware2 = new class ($executionOrder) implements MiddlewareInterface {
            /** @param array<string> $executionOrder */
            public function __construct(
                /** @phpstan-ignore property.onlyWritten */
                private array &$executionOrder
            ) {
            }

            public function process(CommandInterface $command, CommandHandlerInterface $handler): CommandResultInterface
            {
                $this->executionOrder[] = 'middleware2';
                return $handler->handle($command);
            }
        };

        $expectedResult = new CommandResult($this->command, CommandStatus::Success, 'final result');

        $this->handler->expects($this->once())
            ->method('handle')
            ->with($this->command)
            ->willReturnCallback(function () use (&$executionOrder, $expectedResult) {
                $executionOrder[] = 'handler';
                return $expectedResult;
            });

        $this->middlewarePipe->pipe($middleware1);
        $this->middlewarePipe->pipe($middleware2);

        $result = $this->middlewarePipe->process($this->command, $this->handler);

        $this->assertSame($expectedResult, $result);
        $this->assertEquals(['middleware1', 'middleware2', 'handler'], $executionOrder);
    }

    public function testCloneCreatesIndependentCopy(): void
    {
        $middleware = $this->createMock(MiddlewareInterface::class);
        $this->middlewarePipe->pipe($middleware);

        $clonedPipe = clone $this->middlewarePipe;

        $newMiddleware = $this->createMock(MiddlewareInterface::class);
        $clonedPipe->pipe($newMiddleware);

        // Original should have 1 middleware
        $originalReflection = new ReflectionClass($this->middlewarePipe);
        $originalProperty   = $originalReflection->getProperty('pipeline');

        /** @var SplQueue<MiddlewareInterface> $originalPipeline */
        $originalPipeline = $originalProperty->getValue($this->middlewarePipe);

        // Clone should have 2 middleware
        $clonedReflection = new ReflectionClass($clonedPipe);
        $clonedProperty   = $clonedReflection->getProperty('pipeline');

        /** @var SplQueue<MiddlewareInterface> $clonedPipeline */
        $clonedPipeline = $clonedProperty->getValue($clonedPipe);

        $this->assertCount(1, $originalPipeline);
        $this->assertCount(2, $clonedPipeline);
    }

    public function testHandleSupportsVariousReturnTypes(): void
    {
        $testCases = [
            'string result',
            42,
            ['array', 'result'],
            (object) ['object' => 'result'],
            null,
        ];

        foreach ($testCases as $expectedValue) {
            $expectedResult = new CommandResult($this->command, CommandStatus::Success, $expectedValue);

            $middleware = $this->createMock(MiddlewareInterface::class);
            $middleware->expects($this->once())
                ->method('process')
                ->willReturn($expectedResult);

            $pipe = new MiddlewarePipe();
            $pipe->pipe($middleware);

            $result = $pipe->handle($this->command);

            $this->assertSame($expectedResult, $result);
        }
    }

    public function testProcessSupportsVariousReturnTypes(): void
    {
        $testCases = [
            'string result',
            42,
            ['array', 'result'],
            (object) ['object' => 'result'],
            null,
        ];

        foreach ($testCases as $expectedValue) {
            $expectedResult = new CommandResult($this->command, CommandStatus::Success, $expectedValue);

            $handler = $this->createMock(CommandHandlerInterface::class);
            $handler->expects($this->once())
                ->method('handle')
                ->willReturn($expectedResult);

            $result = $this->middlewarePipe->process($this->command, $handler);

            $this->assertSame($expectedResult, $result);
        }
    }

    public function testMiddlewareCanShortCircuitPipeline(): void
    {
        $expectedResult = new CommandResult($this->command, CommandStatus::Success, 'short circuit result');

        $shortCircuitMiddleware = $this->createMock(MiddlewareInterface::class);
        $shortCircuitMiddleware->expects($this->once())
            ->method('process')
            ->willReturn($expectedResult);

        $neverCalledMiddleware = $this->createMock(MiddlewareInterface::class);
        $neverCalledMiddleware->expects($this->never())
            ->method('process');

        $this->middlewarePipe->pipe($shortCircuitMiddleware);
        $this->middlewarePipe->pipe($neverCalledMiddleware);

        $result = $this->middlewarePipe->handle($this->command);

        $this->assertSame($expectedResult, $result);
    }

    public function testMultipleMiddlewarePipeInstancesAreIndependent(): void
    {
        $middleware1 = $this->createMock(MiddlewareInterface::class);
        $middleware2 = $this->createMock(MiddlewareInterface::class);

        $pipe1 = new MiddlewarePipe();
        $pipe2 = new MiddlewarePipe();

        $pipe1->pipe($middleware1);
        $pipe2->pipe($middleware2);

        // Check pipe1 has only middleware1
        $reflection1 = new ReflectionClass($pipe1);
        $property1   = $reflection1->getProperty('pipeline');

        /** @var SplQueue<MiddlewareInterface> $pipeline1 */
        $pipeline1 = $property1->getValue($pipe1);

        // Check pipe2 has only middleware2
        $reflection2 = new ReflectionClass($pipe2);
        $property2   = $reflection2->getProperty('pipeline');

        /** @var SplQueue<MiddlewareInterface> $pipeline2 */
        $pipeline2 = $property2->getValue($pipe2);

        $this->assertCount(1, $pipeline1);
        $this->assertCount(1, $pipeline2);
        $this->assertNotSame($pipeline1, $pipeline2);
    }

    public function testPipeAcceptsDifferentMiddlewareTypes(): void
    {
        $middleware1 = $this->createMock(MiddlewareInterface::class);
        $middleware2 = new class implements MiddlewareInterface {
            public function process(CommandInterface $command, CommandHandlerInterface $handler): CommandResultInterface
            {
                return $handler->handle($command);
            }
        };

        $this->middlewarePipe->pipe($middleware1);
        $this->middlewarePipe->pipe($middleware2);

        $reflection = new ReflectionClass($this->middlewarePipe);
        $property   = $reflection->getProperty('pipeline');

        /** @var SplQueue<MiddlewareInterface> $pipeline */
        $pipeline = $property->getValue($this->middlewarePipe);

        $this->assertCount(2, $pipeline);
    }
}
