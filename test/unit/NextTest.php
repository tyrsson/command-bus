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
use Webware\CommandBus\Command\CommandStatus;
use Webware\CommandBus\CommandInterface;
use Webware\CommandBus\Exception\CommandException;
use Webware\CommandBus\Exception\NextHandlerAlreadyCalledException;
use Webware\CommandBus\MiddlewareInterface;
use Webware\CommandBus\Next;

#[CoversClass(Next::class)]
final class NextTest extends TestCase
{
    #[Test]
    public function handleClonesNextInstanceWhenProcessingMiddleware(): void
    {
        // Arrange
        $command = $this->createCommandStub();

        /** @var MiddlewareInterface&MockObject $middleware */
        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->expects($this->once())
            ->method('process')
            ->with($command, $this->isInstanceOf(Next::class))
            ->willReturnCallback(static fn($cmd, $next) => new CommandResult(
                $command,
                CommandStatus::Success,
                'result',
            ));

        $queue = $this->createMiddlewareQueue();
        $queue->enqueue($middleware);
        $originalNext = new Next($queue);

        // Act
        $result = $originalNext->handle($command);

        // Assert
        $this->assertInstanceOf(CommandResult::class, $result);

        // Verify original Next instance queue is marked as processed
        $reflection    = new ReflectionClass($originalNext);
        $queueProperty = $reflection->getProperty('queue');
        $originalQueue = $queueProperty->getValue($originalNext);

        $this->assertNull($originalQueue);
    }

    #[Test]
    public function handleDequeuesMiddlewareFromQueue(): void
    {
        // Arrange
        $command = $this->createCommandStub();

        /** @var MiddlewareInterface&MockObject $middleware1 */
        $middleware1 = $this->createMock(MiddlewareInterface::class);

        /** @var MiddlewareInterface&MockObject $middleware2 */
        $middleware2 = $this->createStub(MiddlewareInterface::class);

        $middleware1->expects($this->once())
            ->method('process')
            ->with($command, $this->isInstanceOf(Next::class))
            ->willReturn(new CommandResult($command, CommandStatus::Success, 'result1'));

        $queue = $this->createMiddlewareQueue();
        $queue->enqueue($middleware1);
        $queue->enqueue($middleware2);
        $next = new Next($queue);

        // Act
        $next->handle($command);

        // Assert - Use reflection to verify queue state
        $reflection    = new ReflectionClass($next);
        $queueProperty = $reflection->getProperty('queue');
        $internalQueue = $queueProperty->getValue($next);

        // Queue should be null after processing (marked as processed)
        $this->assertNull($internalQueue);
    }

    #[Test]
    public function handleMarksQueueAsProcessedAfterExecution(): void
    {
        // Arrange
        $command = $this->createCommandStub();

        /** @var MiddlewareInterface&MockObject $middleware */
        $middleware = $this->createStub(MiddlewareInterface::class);
        $middleware->method('process')->willReturn(new CommandResult($command, CommandStatus::Success, 'result'));

        $queue = $this->createMiddlewareQueue();
        $queue->enqueue($middleware);
        $next = new Next($queue);

        // Act
        $next->handle($command);

        // Assert - Use reflection to verify queue is null
        $reflection    = new ReflectionClass($next);
        $queueProperty = $reflection->getProperty('queue');
        $internalQueue = $queueProperty->getValue($next);

        $this->assertNull($internalQueue);
    }

    #[Test]
    public function handleProcessesMiddlewareAndReturnsResult(): void
    {
        // Arrange
        $command        = $this->createCommandStub();
        $expectedResult = new CommandResult($command, CommandStatus::Success, 'test_result');

        /** @var MiddlewareInterface&MockObject $middleware */
        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->expects($this->once())
            ->method('process')
            ->with($command, $this->isInstanceOf(Next::class))
            ->willReturn($expectedResult);

        $queue = $this->createMiddlewareQueue();
        $queue->enqueue($middleware);
        $next = new Next($queue);

        // Act
        $result = $next->handle($command);

        // Assert
        $this->assertSame($expectedResult, $result);
    }

    #[Test]
    public function handleSupportsNullReturnFromMiddleware(): void
    {
        // Arrange
        $command        = $this->createCommandStub();
        $expectedResult = new CommandResult($command, CommandStatus::Success, null);

        /** @var MiddlewareInterface&MockObject $middleware */
        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->expects($this->once())
            ->method('process')
            ->with($command, $this->isInstanceOf(Next::class))
            ->willReturn($expectedResult);

        $queue = $this->createMiddlewareQueue();
        $queue->enqueue($middleware);
        $next = new Next($queue);

        // Act
        $result = $next->handle($command);

        // Assert
        $this->assertSame($expectedResult, $result);
    }

    #[Test]
    public function handleSupportsVariousReturnTypes(): void
    {
        $testCases = [
            'string'        => 'test_string',
            'integer'       => 42,
            'float'         => 3.14,
            'boolean_true'  => true,
            'boolean_false' => false,
            'array'         => [
                'key' => 'value',
            ],
            'object'        => (object) ['property' => 'value'],
        ];

        foreach ($testCases as $description => $expectedValue) {
            // Arrange
            $command        = $this->createCommandStub();
            $expectedResult = new CommandResult($command, CommandStatus::Success, $expectedValue);

            /** @var MiddlewareInterface&MockObject $middleware */
            $middleware = $this->createMock(MiddlewareInterface::class);
            $middleware->expects($this->once())
                ->method('process')
                ->with($command, $this->isInstanceOf(Next::class))
                ->willReturn($expectedResult);

            $queue = $this->createMiddlewareQueue();
            $queue->enqueue($middleware);
            $next = new Next($queue);

            // Act
            $result = $next->handle($command);

            // Assert
            $this->assertSame($expectedResult, $result, "Failed for test case: {$description}");
        }
    }

    #[Test]
    public function handleThrowsExceptionWhenQueueAlreadyProcessed(): void
    {
        // Arrange
        $command = $this->createCommandStub();

        /** @var MiddlewareInterface&MockObject $middleware */
        $middleware = $this->createStub(MiddlewareInterface::class);
        $middleware->method('process')->willReturn(new CommandResult($command, CommandStatus::Success, 'result'));

        $queue = $this->createMiddlewareQueue();
        $queue->enqueue($middleware);
        $next = new Next($queue);

        // Process once to mark as processed
        $next->handle($command);

        // Assert
        $this->expectException(NextHandlerAlreadyCalledException::class);
        $this->expectExceptionMessage('The next handler has already been called.');

        // Act - Try to process again
        $next->handle($command);
    }

    #[Test]
    public function handleThrowsExceptionWhenQueueIsEmpty(): void
    {
        // Arrange
        $command    = $this->createCommandStub();
        $emptyQueue = $this->createMiddlewareQueue();
        $next       = new Next($emptyQueue);

        // Assert
        $this->expectException(CommandException::class);
        $this->expectExceptionMessage('No command handler found for command class');

        // Act
        $next->handle($command);
    }

    #[Test]
    public function handleWorksWithMultipleMiddlewareInSequence(): void
    {
        // Arrange
        $command        = $this->createCommandStub();
        $expectedResult = new CommandResult($command, CommandStatus::Success, 'result1');

        /** @var MiddlewareInterface&MockObject $middleware1 */
        $middleware1 = $this->createMock(MiddlewareInterface::class);

        /** @var MiddlewareInterface&MockObject $middleware2 */
        $middleware2 = $this->createStub(MiddlewareInterface::class);

        /** @var MiddlewareInterface&MockObject $middleware3 */
        $middleware3 = $this->createStub(MiddlewareInterface::class);

        $middleware1->expects($this->once())
            ->method('process')
            ->with($command, $this->isInstanceOf(Next::class))
            ->willReturn($expectedResult);

        $queue = $this->createMiddlewareQueue();
        $queue->enqueue($middleware1);
        $queue->enqueue($middleware2);
        $queue->enqueue($middleware3);

        $next = new Next($queue);

        // Act
        $result = $next->handle($command);

        // Assert
        $this->assertSame($expectedResult, $result);

        // Verify only first middleware was processed and queue is now null
        $reflection    = new ReflectionClass($next);
        $queueProperty = $reflection->getProperty('queue');
        $internalQueue = $queueProperty->getValue($next);

        $this->assertNull($internalQueue);
    }

    /**
     * Creates a stub CommandInterface instance
     */
    private function createCommandStub(): CommandInterface
    {
        return $this->createStub(CommandInterface::class);
    }

    private function createMiddlewareQueue(): SplQueue
    {
        return new SplQueue();
    }
}
