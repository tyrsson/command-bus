<?php

declare(strict_types=1);

namespace Webware\CommandBusTest\Container;

use PHPUnit\Framework\Attributes\CoversClass;
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory   = new CommandBusFactory();
        $this->container = $this->createMock(ContainerInterface::class);

        // Create an actual MiddlewarePipe instance since it's final and cannot be mocked
        $this->middlewarePipeline = new MiddlewarePipe();
    }

    public function testInvokeReturnsCommandBusWhenMiddlewarePipelineIsAvailable(): void
    {
        $this->container
            ->expects($this->once())
            ->method('has')
            ->with(MiddlewarePipelineInterface::class)
            ->willReturn(true);

        $this->container
            ->expects($this->once())
            ->method('get')
            ->with(MiddlewarePipelineInterface::class)
            ->willReturn($this->middlewarePipeline);

        $result = ($this->factory)($this->container);

        $this->assertInstanceOf(CommandBus::class, $result);
    }

    public function testInvokeThrowsServiceNotFoundExceptionWhenMiddlewarePipelineIsNotAvailable(): void
    {
        $this->container
            ->expects($this->once())
            ->method('has')
            ->with(MiddlewarePipelineInterface::class)
            ->willReturn(false);

        $this->container
            ->expects($this->never())
            ->method('get');

        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage(
            'Service not found: Webware\CommandBus\MiddlewarePipelineInterface was not found in the container'
        );

        ($this->factory)($this->container);
    }

    public function testInvokeChecksContainerForCorrectService(): void
    {
        $this->container
            ->expects($this->once())
            ->method('has')
            ->with($this->identicalTo(MiddlewarePipelineInterface::class))
            ->willReturn(true);

        $this->container
            ->expects($this->once())
            ->method('get')
            ->with($this->identicalTo(MiddlewarePipelineInterface::class))
            ->willReturn($this->middlewarePipeline);

        $result = ($this->factory)($this->container);

        $this->assertInstanceOf(CommandBus::class, $result);
    }

    public function testFactoryCanBeInvokedMultipleTimes(): void
    {
        $this->container
            ->expects($this->exactly(2))
            ->method('has')
            ->with(MiddlewarePipelineInterface::class)
            ->willReturn(true);

        $this->container
            ->expects($this->exactly(2))
            ->method('get')
            ->with(MiddlewarePipelineInterface::class)
            ->willReturn($this->middlewarePipeline);

        $result1 = ($this->factory)($this->container);
        $result2 = ($this->factory)($this->container);

        $this->assertInstanceOf(CommandBus::class, $result1);
        $this->assertInstanceOf(CommandBus::class, $result2);
        $this->assertNotSame($result1, $result2, 'Factory should create new instances each time');
    }

    public function testFactoryCreatesNewInstancesWithSameMiddlewarePipeline(): void
    {
        $this->container
            ->expects($this->exactly(2))
            ->method('has')
            ->with(MiddlewarePipelineInterface::class)
            ->willReturn(true);

        $this->container
            ->expects($this->exactly(2))
            ->method('get')
            ->with(MiddlewarePipelineInterface::class)
            ->willReturn($this->middlewarePipeline);

        $cmdBus1 = ($this->factory)($this->container);
        $cmdBus2 = ($this->factory)($this->container);

        $this->assertInstanceOf(CommandBus::class, $cmdBus1);
        $this->assertInstanceOf(CommandBus::class, $cmdBus2);
        $this->assertNotSame($cmdBus1, $cmdBus2);
    }

    public function testFactoryIsCallable(): void
    {
        // Verify the factory has the __invoke method
        $this->assertSame('__invoke', (new ReflectionClass($this->factory))->getMethod('__invoke')->getName());
    }
}
