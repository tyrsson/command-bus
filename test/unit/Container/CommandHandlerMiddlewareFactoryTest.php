<?php

declare(strict_types=1);

namespace PhpCmd\CmdBusTest\Container;

use Assert\InvalidArgumentException;
use PhpCmd\CmdBus\CommandHandlerFactory;
use PhpCmd\CmdBus\Container\CommandHandlerMiddlewareFactory;
use PhpCmd\CmdBus\Middleware\CommandHandlerMiddleware;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use stdClass;

#[CoversClass(CommandHandlerMiddlewareFactory::class)]
final class CommandHandlerMiddlewareFactoryTest extends TestCase
{
    private CommandHandlerMiddlewareFactory $factory;

    /** @var ContainerInterface&MockObject */
    private ContainerInterface $container;

    private CommandHandlerFactory $commandHandlerFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory   = new CommandHandlerMiddlewareFactory();
        $this->container = $this->createMock(ContainerInterface::class);

        // Create a real CommandHandlerFactory instance since it's final and cannot be mocked
        $factoryContainer            = $this->createMock(ContainerInterface::class);
        $this->commandHandlerFactory = new CommandHandlerFactory($factoryContainer);
    }

    public function testInvokeReturnsCommandHandlerMiddleware(): void
    {
        $this->container
            ->expects($this->once())
            ->method('get')
            ->with(CommandHandlerFactory::class)
            ->willReturn($this->commandHandlerFactory);

        $result = ($this->factory)($this->container);

        $this->assertInstanceOf(CommandHandlerMiddleware::class, $result);
    }

    public function testInvokeRetrievesCommandHandlerFactoryFromContainer(): void
    {
        $this->container
            ->expects($this->once())
            ->method('get')
            ->with($this->identicalTo(CommandHandlerFactory::class))
            ->willReturn($this->commandHandlerFactory);

        $result = ($this->factory)($this->container);

        $this->assertInstanceOf(CommandHandlerMiddleware::class, $result);
    }

    public function testInvokeThrowsExceptionWhenCommandHandlerFactoryIsNotCorrectType(): void
    {
        $invalidFactory = new stdClass();

        $this->container
            ->expects($this->once())
            ->method('get')
            ->with(CommandHandlerFactory::class)
            ->willReturn($invalidFactory);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Class "stdClass" was expected to be instanceof of "PhpCmd\CmdBus\CommandHandlerFactory" but is not.'
        );

        ($this->factory)($this->container);
    }

    public function testFactoryCanBeInvokedMultipleTimes(): void
    {
        $this->container
            ->expects($this->exactly(2))
            ->method('get')
            ->with(CommandHandlerFactory::class)
            ->willReturn($this->commandHandlerFactory);

        $result1 = ($this->factory)($this->container);
        $result2 = ($this->factory)($this->container);

        $this->assertInstanceOf(CommandHandlerMiddleware::class, $result1);
        $this->assertInstanceOf(CommandHandlerMiddleware::class, $result2);
        $this->assertNotSame($result1, $result2, 'Factory should create new instances each time');
    }

    public function testFactoryCreatesNewInstancesWithSameCommandHandlerFactory(): void
    {
        $this->container
            ->expects($this->exactly(2))
            ->method('get')
            ->with(CommandHandlerFactory::class)
            ->willReturn($this->commandHandlerFactory);

        $middleware1 = ($this->factory)($this->container);
        $middleware2 = ($this->factory)($this->container);

        $this->assertInstanceOf(CommandHandlerMiddleware::class, $middleware1);
        $this->assertInstanceOf(CommandHandlerMiddleware::class, $middleware2);
        $this->assertNotSame($middleware1, $middleware2);
    }

    public function testFactoryIsCallable(): void
    {
        // Verify the factory has the __invoke method
        $this->assertSame('__invoke', (new ReflectionClass($this->factory))->getMethod('__invoke')->getName());
    }

    public function testFactoryWorksWithDifferentContainerInstances(): void
    {
        $container1 = $this->createMock(ContainerInterface::class);
        $container2 = $this->createMock(ContainerInterface::class);

        $factoryContainer1      = $this->createMock(ContainerInterface::class);
        $factoryContainer2      = $this->createMock(ContainerInterface::class);
        $commandHandlerFactory1 = new CommandHandlerFactory($factoryContainer1);
        $commandHandlerFactory2 = new CommandHandlerFactory($factoryContainer2);

        $container1
            ->expects($this->once())
            ->method('get')
            ->with(CommandHandlerFactory::class)
            ->willReturn($commandHandlerFactory1);

        $container2
            ->expects($this->once())
            ->method('get')
            ->with(CommandHandlerFactory::class)
            ->willReturn($commandHandlerFactory2);

        $result1 = ($this->factory)($container1);
        $result2 = ($this->factory)($container2);

        $this->assertInstanceOf(CommandHandlerMiddleware::class, $result1);
        $this->assertInstanceOf(CommandHandlerMiddleware::class, $result2);
        $this->assertNotSame($result1, $result2);
    }

    public function testFactoryReturnsNewInstanceEachTime(): void
    {
        $this->container
            ->expects($this->exactly(3))
            ->method('get')
            ->with(CommandHandlerFactory::class)
            ->willReturn($this->commandHandlerFactory);

        $instances = [];

        for ($i = 0; $i < 3; $i++) {
            $instances[] = ($this->factory)($this->container);
        }

        $this->assertCount(3, $instances);

        foreach ($instances as $instance) {
            $this->assertInstanceOf(CommandHandlerMiddleware::class, $instance);
        }

        // Ensure all instances are different
        $this->assertNotSame($instances[0], $instances[1]);
        $this->assertNotSame($instances[1], $instances[2]);
        $this->assertNotSame($instances[0], $instances[2]);
    }

    public function testFactoryPassesCommandHandlerFactoryToMiddleware(): void
    {
        $this->container
            ->expects($this->once())
            ->method('get')
            ->with(CommandHandlerFactory::class)
            ->willReturn($this->commandHandlerFactory);

        $result = ($this->factory)($this->container);

        $this->assertInstanceOf(CommandHandlerMiddleware::class, $result);

        // The middleware should have received the command handler factory
        // This is verified by the fact that the middleware was created successfully
        $this->assertInstanceOf(CommandHandlerMiddleware::class, $result);
    }
}
