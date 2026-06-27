<?php

declare(strict_types=1);

namespace Webware\CommandBusTest\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use Webware\CommandBus\CommandBus;
use Webware\CommandBus\Container\CommandBusFactory;
use Webware\CommandBus\Exception\ServiceNotFoundException;
use Webware\CommandBus\MiddlewarePipe;
use Webware\CommandBus\MiddlewarePipelineInterface;

#[CoversClass(CommandBusFactory::class)]
final class CommandBusFactoryTest extends TestCase
{
    private CommandBusFactory $factory;

    /** @var ContainerInterface&MockObject */
    private ContainerInterface $container;

    private MiddlewarePipe $middlewarePipeline;

    #[Test]
    public function factoryCanBeInvokedMultipleTimes(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->exactly(2))
            ->method('has')
            ->with(MiddlewarePipelineInterface::class)
            ->willReturn(true);

        $container->expects($this->exactly(2))
            ->method('get')
            ->with(MiddlewarePipelineInterface::class)
            ->willReturn($this->middlewarePipeline);

        $result1 = ($this->factory)($container);
        $result2 = ($this->factory)($container);

        static::assertInstanceOf(CommandBus::class, $result1);
        static::assertInstanceOf(CommandBus::class, $result2);
        static::assertNotSame($result1, $result2, 'Factory should create new instances each time');
    }

    #[Test]
    public function factoryCreatesNewInstancesWithSameMiddlewarePipeline(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->exactly(2))
            ->method('has')
            ->with(MiddlewarePipelineInterface::class)
            ->willReturn(true);

        $container->expects($this->exactly(2))
            ->method('get')
            ->with(MiddlewarePipelineInterface::class)
            ->willReturn($this->middlewarePipeline);

        $cmdBus1 = ($this->factory)($container);
        $cmdBus2 = ($this->factory)($container);

        static::assertInstanceOf(CommandBus::class, $cmdBus1);
        static::assertInstanceOf(CommandBus::class, $cmdBus2);
        static::assertNotSame($cmdBus1, $cmdBus2);
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
    public function invokeChecksContainerForCorrectService(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->with(static::identicalTo(MiddlewarePipelineInterface::class))
            ->willReturn(true);

        $container->expects($this->once())
            ->method('get')
            ->with(static::identicalTo(MiddlewarePipelineInterface::class))
            ->willReturn($this->middlewarePipeline);

        $result = ($this->factory)($container);

        static::assertInstanceOf(CommandBus::class, $result);
    }

    #[Test]
    public function invokeReturnsCommandBusWhenMiddlewarePipelineIsAvailable(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->with(MiddlewarePipelineInterface::class)
            ->willReturn(true);

        $container->expects($this->once())
            ->method('get')
            ->with(MiddlewarePipelineInterface::class)
            ->willReturn($this->middlewarePipeline);

        $result = ($this->factory)($container);

        static::assertInstanceOf(CommandBus::class, $result);
    }

    #[Test]
    public function invokeThrowsServiceNotFoundExceptionWhenMiddlewarePipelineIsNotAvailable(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->with(MiddlewarePipelineInterface::class)
            ->willReturn(false);

        $container->expects($this->never())
            ->method('get');

        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage(
            'Service not found: Webware\CommandBus\MiddlewarePipelineInterface was not found in the container',
        );

        ($this->factory)($container);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory   = new CommandBusFactory();
        $this->container = $this->createStub(ContainerInterface::class);

        // Create an actual MiddlewarePipe instance since it's final and cannot be mocked
        $this->middlewarePipeline = new MiddlewarePipe();
    }
}
