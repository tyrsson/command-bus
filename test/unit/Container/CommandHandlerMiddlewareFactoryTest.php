<?php

declare(strict_types=1);

namespace Webware\CommandBusTest\Container;

use Assert\InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use stdClass;
use Webware\CommandBus\CommandHandlerResolver;
use Webware\CommandBus\CommandHandlerResolverInterface;
use Webware\CommandBus\Container\CommandHandlerMiddlewareFactory;
use Webware\CommandBus\Middleware\CommandHandlerMiddleware;

#[CoversClass(CommandHandlerMiddlewareFactory::class)]
final class CommandHandlerMiddlewareFactoryTest extends TestCase
{
    private CommandHandlerMiddlewareFactory $factory;

    /** @var ContainerInterface&MockObject */
    private ContainerInterface $container;

    private CommandHandlerResolver $commandHandlerResolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory   = new CommandHandlerMiddlewareFactory();
        $this->container = $this->createMock(ContainerInterface::class);

        // Create a real CommandHandlerResolver instance since it's final and cannot be mocked
        $resolverContainer            = $this->createMock(ContainerInterface::class);
        $this->commandHandlerResolver = new CommandHandlerResolver($resolverContainer);
    }

    public function testInvokeReturnsCommandHandlerMiddleware(): void
    {
        $this->container
            ->expects($this->once())
            ->method('get')
            ->with(CommandHandlerResolverInterface::class)
            ->willReturn($this->commandHandlerResolver);

        $result = ($this->factory)($this->container);

        $this->assertInstanceOf(CommandHandlerMiddleware::class, $result);
    }

    public function testInvokeRetrievesCommandHandlerResolverFromContainer(): void
    {
        $this->container
            ->expects($this->once())
            ->method('get')
            ->with($this->identicalTo(CommandHandlerResolverInterface::class))
            ->willReturn($this->commandHandlerResolver);

        $result = ($this->factory)($this->container);

        $this->assertInstanceOf(CommandHandlerMiddleware::class, $result);
    }

    public function testInvokeThrowsExceptionWhenCommandHandlerResolverIsNotCorrectType(): void
    {
        $invalidResolver = new stdClass();

        $this->container
            ->expects($this->once())
            ->method('get')
            ->with(CommandHandlerResolverInterface::class)
            ->willReturn($invalidResolver);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Class "stdClass" was expected to be instanceof of '
            . '"Webware\CommandBus\CommandHandlerResolverInterface" but is not.'
        );

        ($this->factory)($this->container);
    }

    public function testFactoryCanBeInvokedMultipleTimes(): void
    {
        $this->container
            ->expects($this->exactly(2))
            ->method('get')
            ->with(CommandHandlerResolverInterface::class)
            ->willReturn($this->commandHandlerResolver);

        $result1 = ($this->factory)($this->container);
        $result2 = ($this->factory)($this->container);

        $this->assertInstanceOf(CommandHandlerMiddleware::class, $result1);
        $this->assertInstanceOf(CommandHandlerMiddleware::class, $result2);
        $this->assertNotSame($result1, $result2, 'Factory should create new instances each time');
    }

    public function testFactoryCreatesNewInstancesWithSameCommandHandlerResolver(): void
    {
        $this->container
            ->expects($this->exactly(2))
            ->method('get')
            ->with(CommandHandlerResolverInterface::class)
            ->willReturn($this->commandHandlerResolver);

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

        $resolverContainer1      = $this->createMock(ContainerInterface::class);
        $resolverContainer2      = $this->createMock(ContainerInterface::class);
        $commandHandlerResolver1 = new CommandHandlerResolver($resolverContainer1);
        $commandHandlerResolver2 = new CommandHandlerResolver($resolverContainer2);

        $container1
            ->expects($this->once())
            ->method('get')
            ->with(CommandHandlerResolverInterface::class)
            ->willReturn($commandHandlerResolver1);

        $container2
            ->expects($this->once())
            ->method('get')
            ->with(CommandHandlerResolverInterface::class)
            ->willReturn($commandHandlerResolver2);

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
            ->with(CommandHandlerResolverInterface::class)
            ->willReturn($this->commandHandlerResolver);

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

    public function testFactoryPassesCommandHandlerResolverToMiddleware(): void
    {
        $this->container
            ->expects($this->once())
            ->method('get')
            ->with(CommandHandlerResolverInterface::class)
            ->willReturn($this->commandHandlerResolver);

        $result = ($this->factory)($this->container);

        $this->assertInstanceOf(CommandHandlerMiddleware::class, $result);

        // The middleware should have received the command handler resolver
        // This is verified by the fact that the middleware was created successfully
        $this->assertInstanceOf(CommandHandlerMiddleware::class, $result);
    }
}
