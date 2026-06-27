<?php

declare(strict_types=1);

namespace Webware\CommandBusTest;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SplQueue;
use Webware\CommandBus\Command\CommandResult;
use Webware\CommandBus\Command\CommandResultInterface;
use Webware\CommandBus\Command\CommandStatus;
use Webware\CommandBus\CommandHandlerInterface;
use Webware\CommandBus\CommandInterface;
use Webware\CommandBus\Exception\CommandException;
use Webware\CommandBus\MiddlewareInterface;
use Webware\CommandBus\MiddlewarePipe;
use Webware\CommandBus\MiddlewarePipelineInterface;

#[CoversClass(MiddlewarePipe::class)]
final class MiddlewarePipeTest extends TestCase
{
    private MiddlewarePipe $middlewarePipe;

    /** @var CommandInterface&MockObject */
    private CommandInterface $command;

    #[Test]
    public function cloneCreatesIndependentCopy(): void
    {
        $middleware = $this->createStub(MiddlewareInterface::class);
        $this->middlewarePipe->pipe($middleware);

        $clonedPipe = clone $this->middlewarePipe;

        $newMiddleware = $this->createStub(MiddlewareInterface::class);
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

        static::assertCount(1, $originalPipeline);
        static::assertCount(2, $clonedPipeline);
    }

    #[Test]
    public function constructorInitializesEmptyPipeline(): void
    {
        $reflection = new ReflectionClass($this->middlewarePipe);
        $property   = $reflection->getProperty('pipeline');

        /** @var SplQueue<MiddlewareInterface> $pipeline */
        $pipeline = $property->getValue($this->middlewarePipe);

        static::assertInstanceOf(SplQueue::class, $pipeline);
        static::assertTrue($pipeline->isEmpty());
    }

    #[Test]
    public function handleMethodExists(): void
    {
        static::assertInstanceOf(CommandHandlerInterface::class, $this->middlewarePipe);
    }

    #[Test]
    public function handleSupportsVariousReturnTypes(): void
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

            static::assertSame($expectedResult, $result);
        }
    }

    #[Test]
    public function handleWithEmptyPipelineCallsEmptyPipelineHandler(): void
    {
        $this->expectException(CommandException::class);

        $this->middlewarePipe->handle($this->command);
    }

    #[Test]
    public function handleWithMiddlewareCallsMiddleware(): void
    {
        $expectedResult = new CommandResult($this->command, CommandStatus::Success, 'middleware result');

        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->expects($this->once())
            ->method('process')
            ->with(
                $this->command,
                static::isInstanceOf(CommandHandlerInterface::class),
            )
            ->willReturn($expectedResult);

        $this->middlewarePipe->pipe($middleware);

        $result = $this->middlewarePipe->handle($this->command);

        static::assertSame($expectedResult, $result);
    }

    #[Test]
    public function middlewareCanShortCircuitPipeline(): void
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

        static::assertSame($expectedResult, $result);
    }

    #[Test]
    public function middlewarePipeImplementsCorrectInterfaces(): void
    {
        static::assertInstanceOf(MiddlewarePipelineInterface::class, $this->middlewarePipe);
        static::assertInstanceOf(MiddlewareInterface::class, $this->middlewarePipe);
        static::assertInstanceOf(CommandHandlerInterface::class, $this->middlewarePipe);
    }

    #[Test]
    public function multipleMiddlewarePipeInstancesAreIndependent(): void
    {
        $middleware1 = $this->createStub(MiddlewareInterface::class);
        $middleware2 = $this->createStub(MiddlewareInterface::class);

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

        static::assertCount(1, $pipeline1);
        static::assertCount(1, $pipeline2);
        static::assertNotSame($pipeline1, $pipeline2);
    }

    #[Test]
    public function pipeAcceptsDifferentMiddlewareTypes(): void
    {
        $middleware1 = $this->createStub(MiddlewareInterface::class);
        $middleware2 = new class() implements MiddlewareInterface {
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

        static::assertCount(2, $pipeline);
    }

    #[Test]
    public function pipeAddsMiddlewareToQueue(): void
    {
        $middleware = $this->createStub(MiddlewareInterface::class);

        $this->middlewarePipe->pipe($middleware);

        $reflection = new ReflectionClass($this->middlewarePipe);
        $property   = $reflection->getProperty('pipeline');

        /** @var SplQueue<MiddlewareInterface> $pipeline */
        $pipeline = $property->getValue($this->middlewarePipe);

        static::assertFalse($pipeline->isEmpty());
        static::assertCount(1, $pipeline);
    }

    #[Test]
    public function pipeAddsMultipleMiddleware(): void
    {
        $middleware1 = $this->createStub(MiddlewareInterface::class);
        $middleware2 = $this->createStub(MiddlewareInterface::class);
        $middleware3 = $this->createStub(MiddlewareInterface::class);

        $this->middlewarePipe->pipe($middleware1);
        $this->middlewarePipe->pipe($middleware2);
        $this->middlewarePipe->pipe($middleware3);

        $reflection = new ReflectionClass($this->middlewarePipe);
        $property   = $reflection->getProperty('pipeline');

        /** @var SplQueue<MiddlewareInterface> $pipeline */
        $pipeline = $property->getValue($this->middlewarePipe);

        static::assertCount(3, $pipeline);
    }

    #[Test]
    public function pipeMethodExists(): void
    {
        static::assertInstanceOf(MiddlewarePipelineInterface::class, $this->middlewarePipe);
    }

    #[Test]
    public function processMethodExists(): void
    {
        static::assertInstanceOf(MiddlewareInterface::class, $this->middlewarePipe);
    }

    #[Test]
    public function processSupportsVariousReturnTypes(): void
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

            static::assertSame($expectedResult, $result);
        }
    }

    #[Test]
    public function processWithEmptyPipelineCallsHandler(): void
    {
        $expectedResult = new CommandResult($this->command, CommandStatus::Success, 'handler result');

        $handler = $this->createMock(CommandHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($this->command)
            ->willReturn($expectedResult);

        $result = $this->middlewarePipe->process($this->command, $handler);

        static::assertSame($expectedResult, $result);
    }

    #[Test]
    public function processWithMiddlewareCallsMiddleware(): void
    {
        $expectedResult = new CommandResult($this->command, CommandStatus::Success, 'middleware result');

        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->expects($this->once())
            ->method('process')
            ->with(
                $this->command,
                static::isInstanceOf(CommandHandlerInterface::class),
            )
            ->willReturn($expectedResult);

        $this->middlewarePipe->pipe($middleware);

        $handler = $this->createStub(CommandHandlerInterface::class);
        $result  = $this->middlewarePipe->process($this->command, $handler);

        static::assertSame($expectedResult, $result);
    }

    #[Test]
    public function processWithMultipleMiddlewareCallsInOrder(): void
    {
        $executionOrder = [];

        $middleware1 = new class($executionOrder) implements MiddlewareInterface {
            /** @param array<string> $executionOrder */
            public function __construct(
                /** @phpstan-ignore property.onlyWritten */
                private array &$executionOrder,
            ) {}

            public function process(CommandInterface $command, CommandHandlerInterface $handler): CommandResultInterface
            {
                $this->executionOrder[] = 'middleware1';

                return $handler->handle($command);
            }
        };

        $middleware2 = new class($executionOrder) implements MiddlewareInterface {
            /** @param array<string> $executionOrder */
            public function __construct(
                /** @phpstan-ignore property.onlyWritten */
                private array &$executionOrder,
            ) {}

            public function process(CommandInterface $command, CommandHandlerInterface $handler): CommandResultInterface
            {
                $this->executionOrder[] = 'middleware2';

                return $handler->handle($command);
            }
        };

        $expectedResult = new CommandResult($this->command, CommandStatus::Success, 'final result');

        $handler = $this->createMock(CommandHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($this->command)
            ->willReturnCallback(static function () use (&$executionOrder, $expectedResult) {
                $executionOrder[] = 'handler';

                return $expectedResult;
            });

        $this->middlewarePipe->pipe($middleware1);
        $this->middlewarePipe->pipe($middleware2);

        $result = $this->middlewarePipe->process($this->command, $handler);

        static::assertSame($expectedResult, $result);
        static::assertEquals(['middleware1', 'middleware2', 'handler'], $executionOrder);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->middlewarePipe = new MiddlewarePipe();
        $this->command        = $this->createStub(CommandInterface::class);
    }
}
