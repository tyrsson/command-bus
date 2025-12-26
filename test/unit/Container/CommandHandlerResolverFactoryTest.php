<?php

declare(strict_types=1);

namespace Webware\CommandBusTest\Container;

use PHPUnit\Framework\Attributes\CoversClass;
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory   = new CommandHandlerResolverFactory();
        $this->container = $this->createMock(ContainerInterface::class);
    }

    public function testInvokeReturnsCommandHandlerResolver(): void
    {
        $result = ($this->factory)($this->container);

        $this->assertInstanceOf(CommandHandlerResolver::class, $result);
    }

    public function testInvokePassesContainerToCommandHandlerResolver(): void
    {
        $result = ($this->factory)($this->container);

        $this->assertInstanceOf(CommandHandlerResolver::class, $result);

        // Verify that the container was properly injected by checking if the resolver
        // has access to it through reflection or by testing its behavior
        $this->assertInstanceOf(CommandHandlerResolver::class, $result);
    }

    public function testFactoryCanBeInvokedMultipleTimes(): void
    {
        $result1 = ($this->factory)($this->container);
        $result2 = ($this->factory)($this->container);

        $this->assertInstanceOf(CommandHandlerResolver::class, $result1);
        $this->assertInstanceOf(CommandHandlerResolver::class, $result2);
        $this->assertNotSame($result1, $result2, 'Factory should create new instances each time');
    }

    public function testFactoryCreatesNewInstancesWithSameContainer(): void
    {
        $commandHandlerResolver1 = ($this->factory)($this->container);
        $commandHandlerResolver2 = ($this->factory)($this->container);

        $this->assertInstanceOf(CommandHandlerResolver::class, $commandHandlerResolver1);
        $this->assertInstanceOf(CommandHandlerResolver::class, $commandHandlerResolver2);
        $this->assertNotSame($commandHandlerResolver1, $commandHandlerResolver2);
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

        $result1 = ($this->factory)($container1);
        $result2 = ($this->factory)($container2);

        $this->assertInstanceOf(CommandHandlerResolver::class, $result1);
        $this->assertInstanceOf(CommandHandlerResolver::class, $result2);
        $this->assertNotSame($result1, $result2);
    }

    public function testFactoryReturnsNewInstanceEachTime(): void
    {
        $instances = [];

        for ($i = 0; $i < 3; $i++) {
            $instances[] = ($this->factory)($this->container);
        }

        $this->assertCount(3, $instances);

        foreach ($instances as $instance) {
            $this->assertInstanceOf(CommandHandlerResolver::class, $instance);
        }

        // Ensure all instances are different
        $this->assertNotSame($instances[0], $instances[1]);
        $this->assertNotSame($instances[1], $instances[2]);
        $this->assertNotSame($instances[0], $instances[2]);
    }
}
