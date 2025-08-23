<?php

declare(strict_types=1);

namespace PhpCmd\CmdBusTest\Middleware;

use PhpCmd\CmdBus\Command\CommandResult;
use PhpCmd\CmdBus\Command\CommandStatus;
use PhpCmd\CmdBus\CommandHandlerInterface;
use PhpCmd\CmdBus\CommandInterface;
use PhpCmd\CmdBus\Middleware\PostCommandHandlerMiddleware;
use PhpCmd\CmdBus\MiddlewareInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

#[CoversClass(PostCommandHandlerMiddleware::class)]
final class PostCommandHandlerMiddlewareTest extends TestCase
{
    private PostCommandHandlerMiddleware $middleware;

    /** @var CommandInterface&MockObject */
    private CommandInterface $command;

    /** @var CommandHandlerInterface&MockObject */
    private CommandHandlerInterface $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new PostCommandHandlerMiddleware();
        $this->command    = $this->createMock(CommandInterface::class);
        $this->handler    = $this->createMock(CommandHandlerInterface::class);
    }

    public function testMiddlewareImplementsCorrectInterface(): void
    {
        $this->assertInstanceOf(MiddlewareInterface::class, $this->middleware);
    }

    public function testProcessWithCommandResultReturnsUnwrappedResult(): void
    {
        $expectedResult = 'unwrapped result';
        $commandResult  = new CommandResult(
            $this->command,
            CommandStatus::Success,
            $expectedResult
        );

        // Handler should not be called when processing CommandResult
        $this->handler->expects($this->never())
            ->method('handle');

        $result = $this->middleware->process($commandResult, $this->handler);

        $this->assertEquals($expectedResult, $result);
    }

    public function testProcessWithCommandResultReturnsResultForSuccessStatus(): void
    {
        $successResult = ['data' => 'success value'];
        $commandResult = new CommandResult(
            $this->command,
            CommandStatus::Success,
            $successResult
        );

        $result = $this->middleware->process($commandResult, $this->handler);

        $this->assertEquals($successResult, $result);
    }

    public function testProcessWithCommandResultReturnsResultForFailureStatus(): void
    {
        $failureResult = new RuntimeException('Something went wrong');
        $commandResult = new CommandResult(
            $this->command,
            CommandStatus::Failure,
            $failureResult
        );

        $result = $this->middleware->process($commandResult, $this->handler);

        $this->assertSame($failureResult, $result);
    }

    public function testProcessWithRegularCommandDelegatesToHandler(): void
    {
        $expectedResult = 'handler result';

        $this->handler->expects($this->once())
            ->method('handle')
            ->with($this->identicalTo($this->command))
            ->willReturn($expectedResult);

        $result = $this->middleware->process($this->command, $this->handler);

        $this->assertEquals($expectedResult, $result);
    }

    public function testProcessWithRegularCommandPassesCommandCorrectly(): void
    {
        $this->handler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function ($passedCommand) {
                return $passedCommand === $this->command;
            }))
            ->willReturn('test result');

        $this->middleware->process($this->command, $this->handler);
    }

    public function testProcessCanHandleDifferentCommandResultTypes(): void
    {
        // Test with null result
        $nullResult      = new CommandResult($this->command, CommandStatus::Success, null);
        $nullReturnValue = $this->middleware->process($nullResult, $this->handler);
        $this->assertNull($nullReturnValue);

        // Test with array result
        $arrayResult      = new CommandResult($this->command, CommandStatus::Success, ['key' => 'value']);
        $arrayReturnValue = $this->middleware->process($arrayResult, $this->handler);
        $this->assertEquals(['key' => 'value'], $arrayReturnValue);

        // Test with object result
        $objectResult      = new CommandResult($this->command, CommandStatus::Success, new stdClass());
        $objectReturnValue = $this->middleware->process($objectResult, $this->handler);
        $this->assertInstanceOf(stdClass::class, $objectReturnValue);
    }
}
