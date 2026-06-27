<?php

declare(strict_types=1);

namespace Webware\CommandBusTest\Middleware;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Webware\CommandBus\Command\CommandResult;
use Webware\CommandBus\Command\CommandStatus;
use Webware\CommandBus\CommandHandlerInterface;
use Webware\CommandBus\CommandHandlerResolverInterface;
use Webware\CommandBus\CommandInterface;
use Webware\CommandBus\Middleware\CommandHandlerMiddleware;
use Webware\CommandBus\MiddlewareInterface;

#[CoversClass(CommandHandlerMiddleware::class)]
final class CommandHandlerMiddlewareTest extends TestCase
{
    private CommandHandlerMiddleware $middleware;

    /** @var CommandHandlerResolverInterface&MockObject */
    private CommandHandlerResolverInterface $resolver;

    /** @var CommandInterface&MockObject */
    private CommandInterface $command;

    /** @var CommandHandlerInterface&MockObject */
    private CommandHandlerInterface $handler;

    /** @var CommandHandlerInterface&MockObject */
    private CommandHandlerInterface $commandHandler;

    #[Test]
    public function constructorAcceptsCommandHandlerResolver(): void
    {
        $resolver   = $this->createStub(CommandHandlerResolverInterface::class);
        $middleware = new CommandHandlerMiddleware($resolver);

        static::assertInstanceOf(CommandHandlerMiddleware::class, $middleware);
    }

    #[Test]
    public function middlewareImplementsCorrectInterfaces(): void
    {
        static::assertInstanceOf(MiddlewareInterface::class, $this->middleware);
    }

    #[Test]
    public function processCallsResolverWithCorrectCommand(): void
    {
        $resolver   = $this->createMock(CommandHandlerResolverInterface::class);
        $middleware = new CommandHandlerMiddleware($resolver);

        $resolver->expects($this->once())
            ->method('resolve')
            ->with(static::identicalTo($this->command))
            ->willReturn($this->commandHandler);

        $this->commandHandler
            ->method('handle')
            ->willReturn(new CommandResult($this->command, CommandStatus::Success, 'result'));

        $this->handler
            ->method('handle')
            ->willReturn(new CommandResult($this->command, CommandStatus::Success, 'final'));

        $middleware->process($this->command, $this->handler);
    }

    #[Test]
    public function processResolvesCommandHandlerAndCallsIt(): void
    {
        $expectedResult = 'test result';
        $commandResult  = new CommandResult($this->command, CommandStatus::Success, $expectedResult);

        $commandHandler = $this->createMock(CommandHandlerInterface::class);
        $handler        = $this->createMock(CommandHandlerInterface::class);
        $resolver       = $this->createMock(CommandHandlerResolverInterface::class);
        $middleware     = new CommandHandlerMiddleware($resolver);

        $resolver->expects($this->once())
            ->method('resolve')
            ->with($this->command)
            ->willReturn($commandHandler);

        $commandHandler->expects($this->once())
            ->method('handle')
            ->with($this->command)
            ->willReturn($commandResult);

        $handler->expects($this->once())
            ->method('handle')
            ->with($commandResult)
            ->willReturn($commandResult);

        $result = $middleware->process($this->command, $handler);

        static::assertSame($commandResult, $result);
    }

    #[Test]
    public function processReturnsCommandResultFromHandler(): void
    {
        $expectedResult = 'command result';
        $commandResult  = new CommandResult($this->command, CommandStatus::Success, $expectedResult);

        $this->resolver->method('resolve')->willReturn($this->commandHandler);

        $this->commandHandler->method('handle')->willReturn($commandResult);

        $this->handler->method('handle')->willReturn($commandResult);

        $result = $this->middleware->process($this->command, $this->handler);

        static::assertSame($commandResult, $result);
        static::assertInstanceOf(CommandResult::class, $result);
        static::assertSame($this->command, $result->getCommand());
        static::assertSame(CommandStatus::Success, $result->getStatus());
        static::assertSame($expectedResult, $result->getResult());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver       = $this->createStub(CommandHandlerResolverInterface::class);
        $this->command        = $this->createStub(CommandInterface::class);
        $this->handler        = $this->createStub(CommandHandlerInterface::class);
        $this->commandHandler = $this->createStub(CommandHandlerInterface::class);
        $this->middleware     = new CommandHandlerMiddleware($this->resolver);
    }
}
