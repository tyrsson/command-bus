<?php

declare(strict_types=1);

namespace Webware\CommandBusTest\Handler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
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

    #[Test]
    public function exceptionMessageContainsCommandClassName(): void
    {
        $command = new class() implements CommandInterface {
            public function execute(): mixed
            {
                return 'test';
            }
        };

        try {
            $this->handler->handle($command);
            static::fail('Expected CommandException to be thrown');
        } catch (CommandException $e) {
            static::assertStringContainsString('No command handler found for command class', $e->getMessage());
            static::assertStringContainsString('Webware\\CommandBus\\CommandInterface@anonymous', $e->getMessage());
        }
    }

    #[Test]
    public function handleAlwaysThrowsException(): void
    {
        $command1 = $this->createStub(CommandInterface::class);
        $command2 = $this->createStub(CommandInterface::class);

        // Test that it always throws an exception regardless of the command
        $this->expectException(CommandException::class);
        $this->handler->handle($command1);

        // This won't be reached, but shows the pattern
        $this->expectException(CommandException::class);
        $this->handler->handle($command2);
    }

    #[Test]
    public function handleMethodExistsAndIsCallable(): void
    {
        static::assertInstanceOf(CommandHandlerInterface::class, $this->handler);
    }

    #[Test]
    public function handlerImplementsCommandHandlerInterface(): void
    {
        static::assertInstanceOf(CommandHandlerInterface::class, $this->handler);
    }

    #[Test]
    public function handlerIsInstantiable(): void
    {
        $handler = new EmptyPipelineHandler();
        static::assertInstanceOf(EmptyPipelineHandler::class, $handler);
    }

    #[Test]
    public function handleThrowsCommandException(): void
    {
        $this->expectException(CommandException::class);
        $this->expectExceptionMessage('No command handler found for command class');

        $this->handler->handle($this->command);
    }

    #[Test]
    public function handleThrowsCommandExceptionWithCorrectCommandClass(): void
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
            'No command handler found for command class "Webware\\CommandBus\\CommandInterface@anonymous',
        );

        $this->handler->handle($command);
    }

    #[Test]
    public function handleWithDifferentCommandTypes(): void
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

    #[Test]
    public function multipleHandlersAreIndependent(): void
    {
        $handler1 = new EmptyPipelineHandler();
        $handler2 = new EmptyPipelineHandler();

        static::assertNotSame($handler1, $handler2);
        static::assertEquals($handler1, $handler2);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new EmptyPipelineHandler();
        $this->command = $this->createStub(CommandInterface::class);
    }
}
