<?php

declare(strict_types=1);

namespace PhpCmdTest;

use PhpCmd\CommandInterface;
use PhpCmd\MiddlewareInterface;
use PhpCmd\Next;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SplQueue;

#[CoversClass(Next::class)]
final class NextTest extends TestCase
{
    #[Test]
    public function constructorClonesQueue(): void
    {
        // Arrange
        $originalQueue = $this->createMiddlewareQueue();
        $middleware = $this->createMockMiddleware();
        $originalQueue->enqueue($middleware);

        // Act
        $next = new Next($originalQueue);

        // Modify original queue to prove it was cloned
        $originalQueue->enqueue($this->createMockMiddleware());

        // Assert - Use reflection to verify the internal queue was cloned
        $reflection = new \ReflectionClass($next);
        $queueProperty = $reflection->getProperty('queue');
        $queueProperty->setAccessible(true);
        $internalQueue = $queueProperty->getValue($next);

        $this->assertInstanceOf(SplQueue::class, $internalQueue);
        $this->assertNotSame($originalQueue, $internalQueue);
        $this->assertCount(1, $internalQueue); // Only original middleware
        $this->assertCount(2, $originalQueue); // Original + added middleware
    }

    #[Test]
    public function handleProcessesMiddlewareAndReturnsResult(): void
    {
        // Arrange
        $expectedResult = 'test_result';
        $command = $this->createMockCommand();

        /** @var MiddlewareInterface&MockObject $middleware */
        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->expects($this->once())
            ->method('process')
            ->with($command)
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
    public function handleDequeuesMiddlewareFromQueue(): void
    {
        // Arrange
        $command = $this->createMockCommand();

        /** @var MiddlewareInterface&MockObject $middleware1 */
        $middleware1 = $this->createMock(MiddlewareInterface::class);
        /** @var MiddlewareInterface&MockObject $middleware2 */
        $middleware2 = $this->createMock(MiddlewareInterface::class);

        $middleware1->expects($this->once())
            ->method('process')
            ->with($command)
            ->willReturn('result1');

        $queue = $this->createMiddlewareQueue();
        $queue->enqueue($middleware1);
        $queue->enqueue($middleware2);
        $next = new Next($queue);

        // Act
        $next->handle($command);

        // Assert - Use reflection to verify queue state
        $reflection = new \ReflectionClass($next);
        $queueProperty = $reflection->getProperty('queue');
        $queueProperty->setAccessible(true);
        $internalQueue = $queueProperty->getValue($next);

        // Queue should be null after processing (marked as processed)
        $this->assertNull($internalQueue);
    }

    #[Test]
    public function handleMarksQueueAsProcessedAfterExecution(): void
    {
        // Arrange
        $command = $this->createMockCommand();

        /** @var MiddlewareInterface&MockObject $middleware */
        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->method('process')->willReturn('result');

        $queue = $this->createMiddlewareQueue();
        $queue->enqueue($middleware);
        $next = new Next($queue);

        // Act
        $next->handle($command);

        // Assert - Use reflection to verify queue is null
        $reflection = new \ReflectionClass($next);
        $queueProperty = $reflection->getProperty('queue');
        $queueProperty->setAccessible(true);
        $internalQueue = $queueProperty->getValue($next);

        $this->assertNull($internalQueue);
    }

    #[Test]
    public function handleThrowsExceptionWhenQueueAlreadyProcessed(): void
    {
        // Arrange
        $command = $this->createMockCommand();

        /** @var MiddlewareInterface&MockObject $middleware */
        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->method('process')->willReturn('result');

        $queue = $this->createMiddlewareQueue();
        $queue->enqueue($middleware);
        $next = new Next($queue);

        // Process once to mark as processed
        $next->handle($command);

        // Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Middleware pipe has already been processed.');

        // Act - Try to process again
        $next->handle($command);
    }

    #[Test]
    public function handleThrowsExceptionWhenQueueIsEmpty(): void
    {
        // Arrange
        $command = $this->createMockCommand();
        $emptyQueue = $this->createMiddlewareQueue();
        $next = new Next($emptyQueue);

        // Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Empty Queue!');

        // Act
        $next->handle($command);
    }

    #[Test]
    public function handleClonesNextInstanceWhenProcessingMiddleware(): void
    {
        // Arrange
        $command = $this->createMockCommand();

        /** @var MiddlewareInterface&MockObject $middleware */
        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->expects($this->once())
            ->method('process')
            ->with($command)
            ->willReturnCallback(function($cmd) {
                // In a real scenario, middleware would call $next->handle()
                // Here we just verify the cloning behavior
                return 'result';
            });

        $queue = $this->createMiddlewareQueue();
        $queue->enqueue($middleware);
        $originalNext = new Next($queue);

        // Act
        $result = $originalNext->handle($command);

        // Assert
        $this->assertSame('result', $result);

        // Verify original Next instance queue is marked as processed
        $reflection = new \ReflectionClass($originalNext);
        $queueProperty = $reflection->getProperty('queue');
        $queueProperty->setAccessible(true);
        $originalQueue = $queueProperty->getValue($originalNext);

        $this->assertNull($originalQueue);
    }

    #[Test]
    public function handleWorksWithMultipleMiddlewareInSequence(): void
    {
        // Arrange
        $command = $this->createMockCommand();

        /** @var MiddlewareInterface&MockObject $middleware1 */
        $middleware1 = $this->createMock(MiddlewareInterface::class);
        /** @var MiddlewareInterface&MockObject $middleware2 */
        $middleware2 = $this->createMock(MiddlewareInterface::class);
        /** @var MiddlewareInterface&MockObject $middleware3 */
        $middleware3 = $this->createMock(MiddlewareInterface::class);

        $middleware1->expects($this->once())
            ->method('process')
            ->with($command)
            ->willReturn('result1');

        $queue = $this->createMiddlewareQueue();
        $queue->enqueue($middleware1);
        $queue->enqueue($middleware2);
        $queue->enqueue($middleware3);

        $next = new Next($queue);

        // Act
        $result = $next->handle($command);

        // Assert
        $this->assertSame('result1', $result);

        // Verify only first middleware was processed and queue is now null
        $reflection = new \ReflectionClass($next);
        $queueProperty = $reflection->getProperty('queue');
        $queueProperty->setAccessible(true);
        $internalQueue = $queueProperty->getValue($next);

        $this->assertNull($internalQueue);
    }

    #[Test]
    public function handleSupportsNullReturnFromMiddleware(): void
    {
        // Arrange
        $command = $this->createMockCommand();

        /** @var MiddlewareInterface&MockObject $middleware */
        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->expects($this->once())
            ->method('process')
            ->with($command)
            ->willReturn(null);

        $queue = $this->createMiddlewareQueue();
        $queue->enqueue($middleware);
        $next = new Next($queue);

        // Act
        $result = $next->handle($command);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function handleSupportsVariousReturnTypes(): void
    {
        $testCases = [
            'string' => 'test_string',
            'integer' => 42,
            'float' => 3.14,
            'boolean_true' => true,
            'boolean_false' => false,
            'array' => ['key' => 'value'],
            'object' => (object) ['property' => 'value'],
        ];

        foreach ($testCases as $description => $expectedResult) {
            // Arrange
            $command = $this->createMockCommand();

            /** @var MiddlewareInterface&MockObject $middleware */
            $middleware = $this->createMock(MiddlewareInterface::class);
            $middleware->expects($this->once())
                ->method('process')
                ->with($command)
                ->willReturn($expectedResult);

            $queue = $this->createMiddlewareQueue();
            $queue->enqueue($middleware);
            $next = new Next($queue);

            // Act
            $result = $next->handle($command);

            // Assert
            $this->assertSame($expectedResult, $result, "Failed for test case: $description");
        }
    }

    /**
     * Creates a mock CommandInterface instance
     */
    private function createMockCommand(): CommandInterface
    {
        return $this->createMock(CommandInterface::class);
    }

    /**
     * Creates a mock MiddlewareInterface instance
     */
    private function createMockMiddleware(): MiddlewareInterface&MockObject
    {
        return $this->createMock(MiddlewareInterface::class);
    }

    /**
     * Creates a properly typed SplQueue for MiddlewareInterface
     *
     * @return SplQueue<MiddlewareInterface>
     */
    private function createMiddlewareQueue(): SplQueue
    {
        /** @var SplQueue<MiddlewareInterface> $queue */
        $queue = new SplQueue();
        return $queue;
    }
}
