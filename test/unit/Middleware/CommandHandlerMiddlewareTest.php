<?php

declare(strict_types=1);

namespace PhpCmd\CmdBusTest\Middleware;

use PhpCmd\CmdBus\Command\CommandResult;
use PhpCmd\CmdBus\Command\CommandStatus;
use PhpCmd\CmdBus\CommandHandlerInterface;
use PhpCmd\CmdBus\CommandHandlerResolverInterface;
use PhpCmd\CmdBus\CommandInterface;
use PhpCmd\CmdBus\Middleware\CommandHandlerMiddleware;
use PhpCmd\CmdBus\MiddlewareInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver       = $this->createMock(CommandHandlerResolverInterface::class);
        $this->command        = $this->createMock(CommandInterface::class);
        $this->handler        = $this->createMock(CommandHandlerInterface::class);
        $this->commandHandler = $this->createMock(CommandHandlerInterface::class);
        $this->middleware     = new CommandHandlerMiddleware($this->resolver);
    }

    public function testMiddlewareImplementsCorrectInterfaces(): void
    {
        $this->assertInstanceOf(MiddlewareInterface::class, $this->middleware);
    }

    public function testConstructorAcceptsCommandHandlerResolver(): void
    {
        $resolver   = $this->createMock(CommandHandlerResolverInterface::class);
        $middleware = new CommandHandlerMiddleware($resolver);

        $this->assertInstanceOf(CommandHandlerMiddleware::class, $middleware);
    }

    public function testProcessResolvesCommandHandlerAndCallsIt(): void
    {
        $expectedResult = 'test result';
        $commandResult  = new CommandResult($this->command, CommandStatus::Success, $expectedResult);

        $this->resolver->expects($this->once())
            ->method('resolve')
            ->with($this->command)
            ->willReturn($this->commandHandler);

        $this->commandHandler->expects($this->once())
            ->method('handle')
            ->with($this->command)
            ->willReturn($commandResult);

        $result = $this->middleware->process($this->command, $this->handler);

        $this->assertSame($commandResult, $result);
    }

    public function testProcessCallsResolverWithCorrectCommand(): void
    {
        $this->resolver->expects($this->once())
            ->method('resolve')
            ->with($this->identicalTo($this->command))
            ->willReturn($this->commandHandler);

        $this->commandHandler->method('handle')
            ->willReturn(new CommandResult($this->command, CommandStatus::Success, 'result'));

        $this->handler->method('handle')
            ->willReturn(new CommandResult($this->command, CommandStatus::Success, 'final'));

        $this->middleware->process($this->command, $this->handler);
    }

    public function testProcessReturnsCommandResultFromHandler(): void
    {
        $expectedResult = 'command result';
        $commandResult  = new CommandResult($this->command, CommandStatus::Success, $expectedResult);

        $this->resolver->method('resolve')
            ->willReturn($this->commandHandler);

        $this->commandHandler->method('handle')
            ->willReturn($commandResult);

        $result = $this->middleware->process($this->command, $this->handler);

        $this->assertSame($commandResult, $result);
        $this->assertInstanceOf(CommandResult::class, $result);
        $this->assertSame($this->command, $result->getCommand());
        $this->assertSame(CommandStatus::Success, $result->getStatus());
        $this->assertSame($expectedResult, $result->getResult());
    }
}
