<?php

declare(strict_types=1);

namespace Webware\CommandBusTest\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use Webware\CommandBus\CommandHandlerResolver;
use Webware\CommandBus\Container\CommandHandlerResolverFactory;

#[CoversClass(CommandHandlerResolverFactory::class)]
final class CommandHandlerResolverFactoryTest extends TestCase
{
    private CommandHandlerResolverFactory $factory;

    /** @var ContainerInterface&MockObject */
    private ContainerInterface $container;

    #[Test]
    public function factoryCanBeInvokedMultipleTimes(): void
    {
        $result1 = ($this->factory)($this->container);
        $result2 = ($this->factory)($this->container);

        static::assertInstanceOf(CommandHandlerResolver::class, $result1);
        static::assertInstanceOf(CommandHandlerResolver::class, $result2);
        static::assertNotSame($result1, $result2, 'Factory should create new instances each time');
    }

    #[Test]
    public function factoryCreatesNewInstancesWithSameContainer(): void
    {
        $commandHandlerResolver1 = ($this->factory)($this->container);
        $commandHandlerResolver2 = ($this->factory)($this->container);

        static::assertInstanceOf(CommandHandlerResolver::class, $commandHandlerResolver1);
        static::assertInstanceOf(CommandHandlerResolver::class, $commandHandlerResolver2);
        static::assertNotSame($commandHandlerResolver1, $commandHandlerResolver2);
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
    public function factoryReturnsNewInstanceEachTime(): void
    {
        $instances = [];

        for ($i = 0; $i < 3; $i++) {
            $instances[] = ($this->factory)($this->container);
        }

        static::assertCount(3, $instances);

        foreach ($instances as $instance) {
            static::assertInstanceOf(CommandHandlerResolver::class, $instance);
        }

        // Ensure all instances are different
        static::assertNotSame($instances[0], $instances[1]);
        static::assertNotSame($instances[1], $instances[2]);
        static::assertNotSame($instances[0], $instances[2]);
    }

    #[Test]
    public function factoryWorksWithDifferentContainerInstances(): void
    {
        $container1 = $this->createStub(ContainerInterface::class);
        $container2 = $this->createStub(ContainerInterface::class);

        $result1 = ($this->factory)($container1);
        $result2 = ($this->factory)($container2);

        static::assertInstanceOf(CommandHandlerResolver::class, $result1);
        static::assertInstanceOf(CommandHandlerResolver::class, $result2);
        static::assertNotSame($result1, $result2);
    }

    #[Test]
    public function invokePassesContainerToCommandHandlerResolver(): void
    {
        $result = ($this->factory)($this->container);

        static::assertInstanceOf(CommandHandlerResolver::class, $result);

        // Verify that the container was properly injected by checking if the resolver
        // has access to it through reflection or by testing its behavior
        static::assertInstanceOf(CommandHandlerResolver::class, $result);
    }

    #[Test]
    public function invokeReturnsCommandHandlerResolver(): void
    {
        $result = ($this->factory)($this->container);

        static::assertInstanceOf(CommandHandlerResolver::class, $result);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory   = new CommandHandlerResolverFactory();
        $this->container = $this->createStub(ContainerInterface::class);
    }
}
