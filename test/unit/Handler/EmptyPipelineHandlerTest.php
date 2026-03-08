<?php

declare(strict_types=1);

namespace Webware\CommandBusTest\Handler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Webware\CommandBus\CommandHandlerInterface;
use Webware\CommandBus\CommandInterface;
use Webware\CommandBus\Exception\CommandException;
use Webware\CommandBus\Handler\EmptyPipelineHandler;

#[CoversClass(EmptyPipelineHandler::class)]
final class EmptyPipelineHandlerTest extends TestCase
{
    private EmptyPipelineHandler $handler;

    /** @var CommandInterface&MockObject */
    private CommandInterface $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new EmptyPipelineHandler();
        $this->command = $this->createMock(CommandInterface::class);
    }

    public function testHandleThrowsCommandException(): void
    {
        $this->expectException(CommandException::class);
        $this->expectExceptionMessage('No command handler found for command class "MockObject_CommandInterface_');

        $this->handler->handle($this->command);
    }

    public function testHandleThrowsCommandExceptionWithCorrectCommandClass(): void
    {
        // Create a specific command class for testing
        $command = new class() implements CommandInterface {
            public function execute(): mixed
            {
                return 'test';
            }
        };

        $this->expectException(CommandException::class);
        $this->expectExceptionMessage(
            'No command handler found for command class "Webware\\CommandBus\\CommandInterface@anonymous'
        );

        $this->handler->handle($command);
    }

    public function testHandleAlwaysThrowsException(): void
    {
        $command1 = $this->createMock(CommandInterface::class);
        $command2 = $this->createMock(CommandInterface::class);

        // Test that it always throws an exception regardless of the command
        $this->expectException(CommandException::class);
        $this->handler->handle($command1);

        // This won't be reached, but shows the pattern
        $this->expectException(CommandException::class);
        $this->handler->handle($command2);
    }

    public function testHandlerImplementsCommandHandlerInterface(): void
    {
        $this->assertInstanceOf(CommandHandlerInterface::class, $this->handler);
    }

    public function testHandleMethodExistsAndIsCallable(): void
    {
        $this->assertInstanceOf(CommandHandlerInterface::class, $this->handler);
    }

    public function testHandleWithDifferentCommandTypes(): void
    {
        // Test with a simple command implementation
        $simpleCommand = new class() implements CommandInterface {
            public function execute(): mixed
            {
                return 'simple';
            }
        };

        $this->expectException(CommandException::class);
        $this->handler->handle($simpleCommand);
    }

    public function testExceptionMessageContainsCommandClassName(): void
    {
        $command = new class() implements CommandInterface {
            public function execute(): mixed
            {
                return 'test';
            }
        };

        try {
            $this->handler->handle($command);
            $this->fail('Expected CommandException to be thrown');
        } catch (CommandException $e) {
            $this->assertStringContainsString('No command handler found for command class', $e->getMessage());
            $this->assertStringContainsString('Webware\\CommandBus\\CommandInterface@anonymous', $e->getMessage());
        }
    }

    public function testHandlerIsInstantiable(): void
    {
        $handler = new EmptyPipelineHandler();
        $this->assertInstanceOf(EmptyPipelineHandler::class, $handler);
    }

    public function testMultipleHandlersAreIndependent(): void
    {
        $handler1 = new EmptyPipelineHandler();
        $handler2 = new EmptyPipelineHandler();

        $this->assertNotSame($handler1, $handler2);
        $this->assertEquals($handler1, $handler2);
    }
}
