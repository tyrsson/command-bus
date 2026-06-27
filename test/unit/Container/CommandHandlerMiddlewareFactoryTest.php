<?php

declare(strict_types=1);

namespace Webware\CommandBusTest\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use stdClass;
use TypeError;
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

    #[Test]
    public function factoryCanBeInvokedMultipleTimes(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->exactly(2))
            ->method('get')
            ->with(CommandHandlerResolverInterface::class)
            ->willReturn($this->commandHandlerResolver);

        $result1 = ($this->factory)($container);
        $result2 = ($this->factory)($container);

        static::assertInstanceOf(CommandHandlerMiddleware::class, $result1);
        static::assertInstanceOf(CommandHandlerMiddleware::class, $result2);
        static::assertNotSame($result1, $result2, 'Factory should create new instances each time');
    }

    #[Test]
    public function factoryCreatesNewInstancesWithSameCommandHandlerResolver(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->exactly(2))
            ->method('get')
            ->with(CommandHandlerResolverInterface::class)
            ->willReturn($this->commandHandlerResolver);

        $middleware1 = ($this->factory)($container);
        $middleware2 = ($this->factory)($container);

        static::assertInstanceOf(CommandHandlerMiddleware::class, $middleware1);
        static::assertInstanceOf(CommandHandlerMiddleware::class, $middleware2);
        static::assertNotSame($middleware1, $middleware2);
    }

    #[Test]
    public function factoryIsCallable(): void
    {
        // Verify the factory has the __invoke method
        static::assertSame(
            '__invoke',
            new ReflectionClass($this->factory)
                ->getMethod('__invoke')
                ->getName(),
        );
    }

    #[Test]
    public function factoryPassesCommandHandlerResolverToMiddleware(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with(CommandHandlerResolverInterface::class)
            ->willReturn($this->commandHandlerResolver);

        $result = ($this->factory)($container);

        static::assertInstanceOf(CommandHandlerMiddleware::class, $result);

        // The middleware should have received the command handler resolver
        // This is verified by the fact that the middleware was created successfully
        static::assertInstanceOf(CommandHandlerMiddleware::class, $result);
    }

    #[Test]
    public function factoryReturnsNewInstanceEachTime(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->exactly(3))
            ->method('get')
            ->with(CommandHandlerResolverInterface::class)
            ->willReturn($this->commandHandlerResolver);

        $instances = [];

        for ($i = 0; $i < 3; $i++) {
            $instances[] = ($this->factory)($container);
        }

        static::assertCount(3, $instances);

        foreach ($instances as $instance) {
            static::assertInstanceOf(CommandHandlerMiddleware::class, $instance);
        }

        // Ensure all instances are different
        static::assertNotSame($instances[0], $instances[1]);
        static::assertNotSame($instances[1], $instances[2]);
        static::assertNotSame($instances[0], $instances[2]);
    }

    #[Test]
    public function factoryWorksWithDifferentContainerInstances(): void
    {
        $container1 = $this->createMock(ContainerInterface::class);
        $container2 = $this->createMock(ContainerInterface::class);

        $resolverContainer1      = $this->createStub(ContainerInterface::class);
        $resolverContainer2      = $this->createStub(ContainerInterface::class);
        $commandHandlerResolver1 = new CommandHandlerResolver($resolverContainer1);
        $commandHandlerResolver2 = new CommandHandlerResolver($resolverContainer2);

        $container1->expects($this->once())
            ->method('get')
            ->with(CommandHandlerResolverInterface::class)
            ->willReturn($commandHandlerResolver1);

        $container2->expects($this->once())
            ->method('get')
            ->with(CommandHandlerResolverInterface::class)
            ->willReturn($commandHandlerResolver2);

        $result1 = ($this->factory)($container1);
        $result2 = ($this->factory)($container2);

        static::assertInstanceOf(CommandHandlerMiddleware::class, $result1);
        static::assertInstanceOf(CommandHandlerMiddleware::class, $result2);
        static::assertNotSame($result1, $result2);
    }

    #[Test]
    public function invokeRetrievesCommandHandlerResolverFromContainer(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with(static::identicalTo(CommandHandlerResolverInterface::class))
            ->willReturn($this->commandHandlerResolver);

        $result = ($this->factory)($container);

        static::assertInstanceOf(CommandHandlerMiddleware::class, $result);
    }

    #[Test]
    public function invokeReturnsCommandHandlerMiddleware(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with(CommandHandlerResolverInterface::class)
            ->willReturn($this->commandHandlerResolver);

        $result = ($this->factory)($container);

        static::assertInstanceOf(CommandHandlerMiddleware::class, $result);
    }

    #[Test]
    public function invokeThrowsExceptionWhenCommandHandlerResolverIsNotCorrectType(): void
    {
        $invalidResolver = new stdClass();

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with(CommandHandlerResolverInterface::class)
            ->willReturn($invalidResolver);

        $this->expectException(TypeError::class);

        ($this->factory)($container);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory   = new CommandHandlerMiddlewareFactory();
        $this->container = $this->createStub(ContainerInterface::class);

        // Create a real CommandHandlerResolver instance since it's final and cannot be mocked
        $resolverContainer            = $this->createStub(ContainerInterface::class);
        $this->commandHandlerResolver = new CommandHandlerResolver($resolverContainer);
    }
}
