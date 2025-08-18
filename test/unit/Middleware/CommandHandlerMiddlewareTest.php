<?php

declare(strict_types=1);

namespace PhpCmd\CmdBusTest\Middleware;

use PhpCmd\CmdBus\CommandHandlerFactory;
use PhpCmd\CmdBus\CommandHandlerInterface;
use PhpCmd\CmdBus\CommandInterface;
use PhpCmd\CmdBus\ConfigProvider;
use PhpCmd\CmdBus\Middleware\CommandHandlerMiddleware;
use PhpCmd\CmdBus\MiddlewareInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

use function get_class;

#[CoversClass(CommandHandlerMiddleware::class)]
final class CommandHandlerMiddlewareTest extends TestCase
{
    private CommandHandlerMiddleware $middleware;

    private CommandHandlerFactory $factory;

    /** @var CommandInterface&MockObject */
    private CommandInterface $command;

    /** @var CommandHandlerInterface&MockObject */
    private CommandHandlerInterface $handler;

    /** @var CommandHandlerInterface&MockObject */
    private CommandHandlerInterface $commandHandler;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a real CommandHandlerFactory since it's final
        $container            = $this->createMock(ContainerInterface::class);
        $this->factory        = new CommandHandlerFactory($container);
        $this->command        = $this->createMock(CommandInterface::class);
        $this->handler        = $this->createMock(CommandHandlerInterface::class);
        $this->commandHandler = $this->createMock(CommandHandlerInterface::class);
        $this->middleware     = new CommandHandlerMiddleware($this->factory);
    }

    public function testMiddlewareImplementsCorrectInterfaces(): void
    {
        $this->assertInstanceOf(MiddlewareInterface::class, $this->middleware);
        $this->assertInstanceOf(CommandHandlerInterface::class, $this->middleware);
    }

    public function testProcessMethodExists(): void
    {
        $this->assertInstanceOf(MiddlewareInterface::class, $this->middleware);
    }

    public function testHandleMethodExists(): void
    {
        $this->assertInstanceOf(CommandHandlerInterface::class, $this->middleware);
    }

    public function testConstructorAcceptsCommandHandlerFactory(): void
    {
        $container  = $this->createMock(ContainerInterface::class);
        $factory    = new CommandHandlerFactory($container);
        $middleware = new CommandHandlerMiddleware($factory);

        $this->assertInstanceOf(CommandHandlerMiddleware::class, $middleware);
    }

    public function testProcessInvokesFactoryWithCommand(): void
    {
        // We need to test this with a working setup since CommandHandlerFactory is final
        // Create a mock container that will provide a command handler
        $container = $this->createMock(ContainerInterface::class);

        // Mock the config structure that CommandHandlerFactory expects
        $config = [
            ConfigProvider::class => [
                'command-map' => [
                    get_class($this->command) => 'TestHandler',
                ],
            ],
        ];

        $container->method('get')
            ->willReturnCallback(function ($service) use ($config) {
                return match ($service) {
                    'config'      => $config,
                    'TestHandler' => $this->commandHandler,
                    default       => null,
                };
            });

        $container->method('has')
            ->with('TestHandler')
            ->willReturn(true);

        $this->commandHandler->expects($this->once())
            ->method('handle')
            ->with($this->command)
            ->willReturn('test result');

        $factory    = new CommandHandlerFactory($container);
        $middleware = new CommandHandlerMiddleware($factory);
        $result     = $middleware->process($this->command, $this->handler);

        $this->assertEquals('test result', $result);
    }

    public function testProcessReturnsResultFromCommandHandler(): void
    {
        $expectedResult = 'command result';

        // Setup the mock container and config
        $container = $this->createMock(ContainerInterface::class);
        $config    = [
            ConfigProvider::class => [
                'command-map' => [
                    get_class($this->command) => 'TestHandler',
                ],
            ],
        ];

        $container->method('get')
            ->willReturnCallback(function ($service) use ($config) {
                return match ($service) {
                    'config'      => $config,
                    'TestHandler' => $this->commandHandler,
                    default       => null,
                };
            });

        $container->method('has')
            ->with('TestHandler')
            ->willReturn(true);

        $this->commandHandler->expects($this->once())
            ->method('handle')
            ->with($this->command)
            ->willReturn($expectedResult);

        $factory    = new CommandHandlerFactory($container);
        $middleware = new CommandHandlerMiddleware($factory);
        $result     = $middleware->process($this->command, $this->handler);

        $this->assertEquals($expectedResult, $result);
    }

    public function testHandleCallsInternalHandler(): void
    {
        $expectedResult = 'handle result';

        // Setup the middleware with a working factory
        $container = $this->createMock(ContainerInterface::class);
        $config    = [
            ConfigProvider::class => [
                'command-map' => [
                    get_class($this->command) => 'TestHandler',
                ],
            ],
        ];

        $container->method('get')
            ->willReturnCallback(function ($service) use ($config) {
                return match ($service) {
                    'config'      => $config,
                    'TestHandler' => $this->commandHandler,
                    default       => null,
                };
            });

        $container->method('has')
            ->with('TestHandler')
            ->willReturn(true);

        $this->commandHandler->expects($this->exactly(2))
            ->method('handle')
            ->with($this->command)
            ->willReturn($expectedResult);

        $factory    = new CommandHandlerFactory($container);
        $middleware = new CommandHandlerMiddleware($factory);

        // First call process to set up the internal handler
        $middleware->process($this->command, $this->handler);

        // Then call handle directly
        $result = $middleware->handle($this->command);

        $this->assertEquals($expectedResult, $result);
    }

    public function testProcessIgnoresPassedHandler(): void
    {
        // The passed handler should not be used, only the one from the factory
        $container = $this->createMock(ContainerInterface::class);
        $config    = [
            ConfigProvider::class => [
                'command-map' => [
                    get_class($this->command) => 'TestHandler',
                ],
            ],
        ];

        $container->method('get')
            ->willReturnCallback(function ($service) use ($config) {
                return match ($service) {
                    'config'      => $config,
                    'TestHandler' => $this->commandHandler,
                    default       => null,
                };
            });

        $container->method('has')
            ->with('TestHandler')
            ->willReturn(true);

        // The passed handler should never be called
        $this->handler->expects($this->never())
            ->method('handle');

        // The factory-created handler should be called
        $this->commandHandler->expects($this->once())
            ->method('handle')
            ->with($this->command)
            ->willReturn('factory result');

        $factory    = new CommandHandlerFactory($container);
        $middleware = new CommandHandlerMiddleware($factory);
        $result     = $middleware->process($this->command, $this->handler);

        $this->assertEquals('factory result', $result);
    }

    public function testMiddlewareCanBeInstantiatedMultipleTimes(): void
    {
        $container   = $this->createMock(ContainerInterface::class);
        $factory1    = new CommandHandlerFactory($container);
        $factory2    = new CommandHandlerFactory($container);
        $middleware1 = new CommandHandlerMiddleware($factory1);
        $middleware2 = new CommandHandlerMiddleware($factory2);
        $this->assertInstanceOf(CommandHandlerMiddleware::class, $middleware1);
        $this->assertInstanceOf(CommandHandlerMiddleware::class, $middleware2);
        $this->assertNotSame($middleware1, $middleware2);
    }

    public function testProcessSetsInternalHandlerForSubsequentHandleCalls(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $config    = [
            ConfigProvider::class => [
                'command-map' => [
                    get_class($this->command) => 'TestHandler',
                ],
            ],
        ];

        $container->method('get')
            ->willReturnCallback(function ($service) use ($config) {
                return match ($service) {
                    'config'      => $config,
                    'TestHandler' => $this->commandHandler,
                    default       => null,
                };
            });

        $container->method('has')
            ->with('TestHandler')
            ->willReturn(true);

        $this->commandHandler->expects($this->exactly(3))
            ->method('handle')
            ->with($this->command)
            ->willReturn('consistent result');

        $factory    = new CommandHandlerFactory($container);
        $middleware = new CommandHandlerMiddleware($factory);

        // Call process to set up the handler
        $result1 = $middleware->process($this->command, $this->handler);

        // Subsequent handle calls should use the same internal handler
        $result2 = $middleware->handle($this->command);
        $result3 = $middleware->handle($this->command);

        $this->assertEquals('consistent result', $result1);
        $this->assertEquals('consistent result', $result2);
        $this->assertEquals('consistent result', $result3);
    }
}
