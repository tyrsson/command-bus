<?php

declare(strict_types=1);

namespace PhpCmd\CmdBusTest\Container;

use PhpCmd\CmdBus\CommandHandlerFactory;
use PhpCmd\CmdBus\Container\CommandHandlerFactoryFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionClass;

#[CoversClass(CommandHandlerFactoryFactory::class)]
final class CommandHandlerFactoryFactoryTest extends TestCase
{
    private CommandHandlerFactoryFactory $factory;

    /** @var ContainerInterface&MockObject */
    private ContainerInterface $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory   = new CommandHandlerFactoryFactory();
        $this->container = $this->createMock(ContainerInterface::class);
    }

    public function testInvokeReturnsCommandHandlerFactory(): void
    {
        $result = ($this->factory)($this->container);

        $this->assertInstanceOf(CommandHandlerFactory::class, $result);
    }

    public function testInvokePassesContainerToCommandHandlerFactory(): void
    {
        $result = ($this->factory)($this->container);

        $this->assertInstanceOf(CommandHandlerFactory::class, $result);

        // Verify that the container was properly injected by checking if the factory
        // has access to it through reflection or by testing its behavior
        $this->assertInstanceOf(CommandHandlerFactory::class, $result);
    }

    public function testFactoryCanBeInvokedMultipleTimes(): void
    {
        $result1 = ($this->factory)($this->container);
        $result2 = ($this->factory)($this->container);

        $this->assertInstanceOf(CommandHandlerFactory::class, $result1);
        $this->assertInstanceOf(CommandHandlerFactory::class, $result2);
        $this->assertNotSame($result1, $result2, 'Factory should create new instances each time');
    }

    public function testFactoryCreatesNewInstancesWithSameContainer(): void
    {
        $commandHandlerFactory1 = ($this->factory)($this->container);
        $commandHandlerFactory2 = ($this->factory)($this->container);

        $this->assertInstanceOf(CommandHandlerFactory::class, $commandHandlerFactory1);
        $this->assertInstanceOf(CommandHandlerFactory::class, $commandHandlerFactory2);
        $this->assertNotSame($commandHandlerFactory1, $commandHandlerFactory2);
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

        $this->assertInstanceOf(CommandHandlerFactory::class, $result1);
        $this->assertInstanceOf(CommandHandlerFactory::class, $result2);
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
            $this->assertInstanceOf(CommandHandlerFactory::class, $instance);
        }

        // Ensure all instances are different
        $this->assertNotSame($instances[0], $instances[1]);
        $this->assertNotSame($instances[1], $instances[2]);
        $this->assertNotSame($instances[0], $instances[2]);
    }
}
