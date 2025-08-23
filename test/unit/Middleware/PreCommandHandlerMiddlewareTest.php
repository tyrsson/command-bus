<?php

declare(strict_types=1);

namespace PhpCmd\CmdBusTest\Middleware;

use PhpCmd\CmdBus\CommandHandlerInterface;
use PhpCmd\CmdBus\CommandInterface;
use PhpCmd\CmdBus\Middleware\PreCommandHandlerMiddleware;
use PhpCmd\CmdBus\MiddlewareInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(PreCommandHandlerMiddleware::class)]
final class PreCommandHandlerMiddlewareTest extends TestCase
{
    private PreCommandHandlerMiddleware $middleware;

    /** @var CommandInterface&MockObject */
    private CommandInterface $command;

    /** @var CommandHandlerInterface&MockObject */
    private CommandHandlerInterface $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new PreCommandHandlerMiddleware();
        $this->command    = $this->createMock(CommandInterface::class);
        $this->handler    = $this->createMock(CommandHandlerInterface::class);
    }

    public function testMiddlewareImplementsCorrectInterface(): void
    {
        $this->assertInstanceOf(MiddlewareInterface::class, $this->middleware);
    }

    public function testProcessDelegatesToHandler(): void
    {
        $expectedResult = 'handler result';

        $this->handler->expects($this->once())
            ->method('handle')
            ->with($this->identicalTo($this->command))
            ->willReturn($expectedResult);

        $result = $this->middleware->process($this->command, $this->handler);

        $this->assertEquals($expectedResult, $result);
    }

    public function testProcessPassesCommandCorrectly(): void
    {
        $this->handler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function ($passedCommand) {
                return $passedCommand === $this->command;
            }))
            ->willReturn('test result');

        $this->middleware->process($this->command, $this->handler);
    }

    public function testProcessReturnsHandlerResult(): void
    {
        $handlerResults = [
            'string result',
            ['array', 'result'],
            42,
            null,
            new stdClass(),
        ];

        foreach ($handlerResults as $expectedResult) {
            $handler = $this->createMock(CommandHandlerInterface::class);
            $handler->expects($this->once())
                ->method('handle')
                ->willReturn($expectedResult);

            $result = $this->middleware->process($this->command, $handler);

            $this->assertSame($expectedResult, $result);
        }
    }

    public function testProcessCallsHandlerExactlyOnce(): void
    {
        $this->handler->expects($this->once())
            ->method('handle')
            ->willReturn('result');

        $this->middleware->process($this->command, $this->handler);
    }

    public function testProcessWithDifferentCommands(): void
    {
        $command1 = $this->createMock(CommandInterface::class);
        $command2 = $this->createMock(CommandInterface::class);

        $this->handler->expects($this->exactly(2))
            ->method('handle')
            ->willReturnCallback(function ($command) use ($command1, $command2) {
                if ($command === $command1) {
                    return 'result1';
                }
                if ($command === $command2) {
                    return 'result2';
                }
                return 'default';
            });

        $result1 = $this->middleware->process($command1, $this->handler);
        $result2 = $this->middleware->process($command2, $this->handler);

        $this->assertEquals('result1', $result1);
        $this->assertEquals('result2', $result2);
    }

    public function testMiddlewareCanBeInstantiatedMultipleTimes(): void
    {
        $middleware1 = new PreCommandHandlerMiddleware();
        $middleware2 = new PreCommandHandlerMiddleware();

        $this->assertInstanceOf(PreCommandHandlerMiddleware::class, $middleware1);
        $this->assertInstanceOf(PreCommandHandlerMiddleware::class, $middleware2);
        $this->assertNotSame($middleware1, $middleware2);
    }
}
